<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use App\Models\UserModel;

/**
 * Gemini Service
 *
 * Core service for interacting with Google's Gemini API. Handles text generation,
 * streaming responses, token counting, cost estimation, and TTS synthesis.
 *
 * Implements automatic fallback through model priorities with quota handling.
 *
 * @package App\Modules\Gemini\Libraries
 */

use App\Modules\Gemini\Models\PromptModel;
use App\Modules\Gemini\Models\UserSettingsModel;

class GeminiService
{
    public const MODEL_PRIORITIES = [
        "gemini-flash-latest",      // Primary: Latest Flash model for speed and efficiency
        "gemini-flash-lite-latest", // Secondary: Lite version for lower latency
        "gemini-3-flash-preview",   // Preview: Latest Flash model with preview features
        "gemini-2.5-flash",         // Fallback: Stable Flash version
        "gemini-2.5-flash-lite",    // Fallback: Stable Lite version
        "gemini-2.0-flash",         // Legacy Fallback
        "gemini-2.0-flash-lite",    // Legacy Fallback
    ];

    public const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    public const SUPPORTED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/webp',
        'audio/mpeg',
        'audio/mp3',
        'audio/wav',
        'video/mov',
        'video/mpeg',
        'video/mp4',
        'video/mpg',
        'video/avi',
        'video/wmv',
        'video/mpegps',
        'video/flv',
        'application/pdf',
        'text/plain'
    ];

    private const PRICING = [
        'tier1' => ['input' => 2.00, 'output' => 12.00, 'limit' => 200000],
        'tier2' => ['input' => 4.00, 'output' => 18.00],
        'audio' => ['input' => 0.50, 'output' => 10.00]
    ];

    /**
     * Constructor with Property Promotion (PHP 8.0+)
     *
     * @param string|null $apiKey API key for Gemini (defaults to env variable)
     * @param mixed $payloadService Service for building model-specific payloads
     * @param UserModel|null $userModel User model for balance management
     * @param mixed $db Database connection for transactions
     */
    public function __construct(
        protected ?string $apiKey = null,
        protected $payloadService = null,
        protected ?UserModel $userModel = null,
        protected $db = null,
        protected $ffmpegService = null,
        protected ?PromptModel $promptModel = null,
        protected ?UserSettingsModel $userSettingsModel = null
    ) {
        $this->apiKey = $apiKey ?? env('GEMINI_API_KEY');
        $this->payloadService = $payloadService ?? service('modelPayloadService');
        $this->userModel = $userModel ?? new UserModel();
        $this->db = $db ?? \Config\Database::connect();
        $this->ffmpegService = service('ffmpegService');
        $this->promptModel = $promptModel ?? new PromptModel();
        $this->userSettingsModel = $userSettingsModel ?? new UserSettingsModel();
    }

    public function processInteraction(int $userId, string $prompt, array $fileParts, array $options): array
    {
        // 1. Context & Setup
        $allParts = $fileParts;
        if (!empty($options['assistant_mode'] ?? true)) {
            // Use centralized prompt construction from MemoryService
            $memoryService = service('memory', $userId);
            $contextData = $memoryService->buildContextualPrompt($prompt);

            if ($contextData['finalPrompt']) {
                array_unshift($allParts, ['text' => $contextData['finalPrompt']]);
            }
        } else {
            array_unshift($allParts, ['text' => $prompt]);
            $contextData = ['memoryService' => null, 'usedInteractionIds' => []];
        }

        if (empty($allParts)) return ['error' => 'No content provided.'];

        // 2. Cost Estimation
        $estimate = $this->estimateCost($allParts);
        $user = $this->userModel->find($userId);
        if ($estimate['status'] && $user->balance < $estimate['costKSH']) {
            return ['error' => "Insufficient balance. Need KSH " . number_format($estimate['costKSH'], 2)];
        }

        // 3. Execution
        $apiResponse = $this->generateContent($allParts);
        if (isset($apiResponse['error'])) return ['error' => $apiResponse['error']];

        // 4. TTS (Optional)
        $audioResult = null;
        if (($options['voice_mode'] ?? false) && !empty($apiResponse['result'])) {
            $audioResult = $this->generateSpeech($apiResponse['result']);
        }

        // 5. Transaction
        $this->db->transStart();

        $costData = $this->calculateCost($apiResponse['usage'] ?? [], $audioResult['usage'] ?? null);
        $this->userModel->deductBalance($userId, number_format($costData['costKSH'], 4, '.', ''));

        // Memory updates
        if (!empty($options['assistant_mode'] ?? true) && isset($contextData['memoryService'])) {
            $contextData['memoryService']->updateMemory(
                $prompt,
                $apiResponse['result'],
                $apiResponse['raw'] ?? '',
                $contextData['usedInteractionIds'] ?? []
            );
        }

        $this->db->transComplete();

        return [
            'result' => $apiResponse['result'],
            'costKSH' => $costData['costKSH'],
            'audioData' => $audioResult['audioData'] ?? null,
            'success' => true
        ];
    }

    public function generateContent(array $parts): array
    {
        if (!$this->apiKey) return ['error' => 'API Key missing.'];

        foreach (self::MODEL_PRIORITIES as $model) {
            log_message('info', "[GeminiService] Attempting generation with model: {$model}");

            $config = $this->payloadService->getPayloadConfig($model, $this->apiKey, $parts);
            if (!$config) {
                log_message('warning', "[GeminiService] No payload config found for model: {$model}");
                continue;
            }

            $result = $this->_executeRequest($config['url'], $config['body'], $model);

            if (isset($result['error']) && str_contains($result['error'], 'Quota exceeded')) {
                log_message('warning', "[GeminiService] Quota exceeded for model: {$model}, trying next model");
                continue;
            }

            if (isset($result['result'])) {
                log_message('info', "[GeminiService] Successfully generated content with model: {$model}");
                return $result;
            }
        }
        log_message('error', '[GeminiService] All models failed or quota exceeded');
        return ['error' => 'All models failed or quota exceeded.'];
    }

    private function _executeRequest(string $url, string $body, string $model = 'unknown'): array
    {
        $maxRetries = 2;
        for ($i = 0; $i <= $maxRetries; $i++) {
            try {
                if ($i > 0) {
                    log_message('info', "[GeminiService] Retry attempt {$i}/{$maxRetries} for model: {$model}");
                }

                $client = \Config\Services::curlrequest();
                $response = $client->post($url, [
                    'body' => $body,
                    'headers' => ['Content-Type' => 'application/json'],
                    'http_errors' => false,
                    'timeout' => 90
                ]);

                $code = $response->getStatusCode();

                if ($code === 429) {
                    $backoffSeconds = 1 * ($i + 1);
                    log_message('warning', "[GeminiService] HTTP 429 (Rate Limit) for model: {$model}, attempt {$i}/{$maxRetries}, backing off {$backoffSeconds}s");
                    sleep($backoffSeconds);
                    continue;
                }

                if ($code !== 200) {
                    $err = json_decode($response->getBody(), true);
                    $errorMsg = $err['error']['message'] ?? "API Error $code";
                    log_message('error', "[GeminiService] HTTP {$code} error for model: {$model} - {$errorMsg}");
                    return ['error' => $errorMsg];
                }

                $data = json_decode($response->getBody(), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    log_message('error', "[GeminiService] JSON Decode Error: " . json_last_error_msg());
                    return ['error' => 'Failed to decode API response.'];
                }

                $text = '';
                foreach ($data['candidates'][0]['content']['parts'] ?? [] as $part) {
                    $text .= $part['text'] ?? '';
                }

                return [
                    'result' => $text,
                    'usage' => $data['usageMetadata'] ?? null,
                    'raw' => $data
                ];
            } catch (\Exception $e) {
                log_message('error', "[GeminiService] Exception for model: {$model}, attempt {$i}/{$maxRetries} - {$e->getMessage()}");
                if ($i === $maxRetries) {
                    log_message('error', "[GeminiService] All retries exhausted for model: {$model}");
                    return ['error' => $e->getMessage()];
                }
            }
        }
        log_message('error', "[GeminiService] Request failed after all retries for model: {$model}");
        return ['error' => 'Request failed after retries.'];
    }

    public function generateStream(array $parts, callable $chunkCallback, callable $completeCallback): void
    {
        if (!$this->apiKey) {
            $chunkCallback(['error' => "Error: API Key missing."]);
            return;
        }

        foreach (self::MODEL_PRIORITIES as $model) {
            $config = $this->payloadService->getPayloadConfig($model, $this->apiKey, $parts, true);
            if (!$config) continue;

            $buffer = '';
            $fullText = '';
            $usage = null;
            $rawChunks = [];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $config['url'],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $config['body'],
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$buffer, &$fullText, &$usage, &$rawChunks, $chunkCallback) {
                    $buffer .= $chunk;
                    $parsed = $this->_processStreamBuffer($buffer);
                    foreach ($parsed['chunks'] as $text) {
                        $fullText .= $text;
                        $chunkCallback($text);
                    }
                    if (!empty($parsed['raw_chunks'])) {
                        $rawChunks = array_merge($rawChunks, $parsed['raw_chunks']);
                    }
                    if ($parsed['usage']) $usage = $parsed['usage'];
                    return strlen($chunk);
                }
            ]);

            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($code === 200) {
                $completeCallback($fullText, $usage, $rawChunks);
                return;
            }
        }
        $chunkCallback(['error' => "Error: Stream failed."]);
    }

    private function _processStreamBuffer(string &$buffer): array
    {
        $result = ['chunks' => [], 'usage' => null, 'raw_chunks' => []];

        // 1. Clean framing characters
        $buffer = ltrim($buffer, ", \n\r\t[");

        // 2. Process all complete objects in buffer
        while (!empty($buffer) && $buffer[0] === '{') {
            $objectFound = false;
            $offset = 0;

            // Search for the matching closing brace
            while (true) {
                $pos = strpos($buffer, '}', $offset);
                if ($pos === false) {
                    break;
                }

                // Try to decode substring up to this brace
                $candidate = substr($buffer, 0, $pos + 1);
                $data = json_decode($candidate, true);

                if (json_last_error() === JSON_ERROR_NONE && $data !== null) {
                    // Valid JSON object found!
                    $objectFound = true;

                    // Extract Data
                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        $result['chunks'][] = $data['candidates'][0]['content']['parts'][0]['text'];
                    }
                    if (isset($data['usageMetadata'])) {
                        $result['usage'] = $data['usageMetadata'];
                    }
                    $result['raw_chunks'][] = $data;

                    // Advance buffer past this object
                    $buffer = substr($buffer, $pos + 1);
                    $buffer = ltrim($buffer, ", \n\r\t");
                    break;
                }

                // Not a valid object yet, continue search
                $offset = $pos + 1;
            }

            if (!$objectFound) {
                break;
            }
        }

        // Handle end of stream array
        if (!empty($buffer) && $buffer[0] === ']') {
            $buffer = '';
        }

        return $result;
    }

    public function calculateCost(array $textUsage, ?array $audioUsage = null): array
    {
        $pricing = self::PRICING;

        // Text
        $promptT = $textUsage['promptTokenCount'] ?? 0;
        $candT = $textUsage['candidatesTokenCount'] ?? 0;
        $totalT = $textUsage['totalTokenCount'] ?? ($promptT + $candT);

        $tier = ($totalT > $pricing['tier1']['limit']) ? 'tier2' : 'tier1';
        $usd = ($promptT / 1e6 * $pricing[$tier]['input']) + ($candT / 1e6 * $pricing[$tier]['output']);

        // Audio
        if ($audioUsage) {
            $aIn = $audioUsage['promptTokenCount'] ?? 0;
            $aOut = $audioUsage['candidatesTokenCount'] ?? 0;
            $usd += ($aIn / 1e6 * $pricing['audio']['input']) + ($aOut / 1e6 * $pricing['audio']['output']);
        }

        return ['costKSH' => $usd * 129];
    }

    public function countTokens(array $parts): array
    {
        $apiKey = trim($this->apiKey);
        if (!$apiKey) {
            return ['status' => false, 'error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $currentModel = "gemini-2.0-flash";
        $countTokensApi = "countTokens";
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:{$countTokensApi}?key=" . urlencode($apiKey);

        $requestPayload = ["contents" => [["parts" => $parts]]];
        $client = \Config\Services::curlrequest();

        try {
            $response = $client->request('POST', $apiUrl, [
                'body' => json_encode($requestPayload),
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody();

            if ($statusCode !== 200) {
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error during token count.';
                log_message('error', "Gemini API countTokens Error: Status {$statusCode} - {$errorMessage}");
                return ['status' => false, 'error' => $errorMessage];
            }

            $responseData = json_decode($responseBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'Gemini API countTokens JSON Decode Error: ' . json_last_error_msg());
                return ['status' => false, 'error' => 'Failed to decode API response.'];
            }
            $totalTokens = $responseData['totalTokens'] ?? 0;

            return ['status' => true, 'totalTokens' => $totalTokens];
        } catch (\Exception $e) {
            log_message('error', 'Gemini API countTokens Exception: ' . $e->getMessage());
            return ['status' => false, 'error' => 'Could not connect to the AI service to estimate cost.'];
        }
    }

    public function estimateCost(array $parts): array
    {
        $response = $this->countTokens($parts);
        if (!$response['status']) {
            return ['status' => false, 'error' => $response['error']];
        }

        $estimatedTokens = $response['totalTokens'];

        // Use Tier 1 pricing for estimation (conservative approach)
        $pricing = self::PRICING['tier1'];

        $estimatedCostUSD = ($estimatedTokens / 1000000) * $pricing['input'];
        $usdToKsh = 129;
        $estimatedCostKSH = $estimatedCostUSD * $usdToKsh;

        return [
            'status' => true,
            'costKSH' => $estimatedCostKSH,
            'totalTokens' => $estimatedTokens
        ];
    }

    public function generateSpeech(string $text): array
    {
        $apiKey = trim($this->apiKey);
        if (!$apiKey) {
            return ['status' => false, 'error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $ttsModel = 'gemini-2.5-flash-preview-tts';
        $apiMethod = 'streamGenerateContent';
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$ttsModel}:{$apiMethod}?key=" . urlencode($apiKey);

        $requestPayload = [
            "contents" => [
                ["role" => "user", "parts" => [["text" => $text]]]
            ],
            "generationConfig" => [
                "responseModalities" => ["audio"],
                "speech_config" => [
                    "voice_config" => [
                        "prebuilt_voice_config" => [
                            "voice_name" => "Zephyr",
                        ]
                    ]
                ]
            ],
        ];

        try {
            $client = \Config\Services::curlrequest([
                'timeout' => 60,
                'connect_timeout' => 15,
            ]);
            $response = $client->request('POST', $apiUrl, [
                'body' => json_encode($requestPayload),
                'headers' => ['Content-Type' => 'application/json'],
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody();

            if ($statusCode !== 200) {
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData[0]['error']['message'] ?? $errorData['error']['message'] ?? 'Unknown API error during speech generation.';
                log_message('error', "Gemini TTS Error: Status {$statusCode} - {$errorMessage}");
                return ['status' => false, 'error' => $errorMessage];
            }

            $responseDataArray = json_decode($responseBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'Gemini TTS Response JSON Decode Error: ' . json_last_error_msg() . ' | Response: ' . $responseBody);
                return ['status' => false, 'error' => 'Failed to decode API speech response.'];
            }

            // Parse the streamed response chunks to aggregate audio data
            $audioData = '';
            $foundAudio = false;
            $usageMetadata = null;

            foreach ($responseDataArray as $chunk) {
                if (isset($chunk['usageMetadata'])) {
                    $usageMetadata = $chunk['usageMetadata'];
                }

                $candidates = $chunk['candidates'][0] ?? null;
                if (!$candidates) continue;

                $parts = $candidates['content']['parts'][0] ?? null;
                if (!$parts) continue;

                if (isset($parts['inlineData']['data'])) {
                    $audioData .= $parts['inlineData']['data'];
                    $foundAudio = true;
                }
            }

            if (!$foundAudio) {
                log_message('error', 'Gemini TTS Error: Audio data not found in the expected location in the response.');
                return ['status' => false, 'error' => 'Failed to retrieve audio data from the AI service.'];
            }

            return ['status' => true, 'audioData' => $audioData, 'usage' => $usageMetadata];
        } catch (\Exception $e) {
            log_message('error', 'Gemini TTS Exception: ' . $e->getMessage());
            return ['status' => false, 'error' => 'Could not connect to the speech synthesis service.'];
        }
    }
    /**
     * Finalizes the streaming interaction by handling billing, memory updates, and audio generation.
     * Use this to keep the Controller 'skinny'.
     */
    public function finalizeStreamInteraction(
        int $userId,
        string $inputText,
        string $fullText,
        ?array $usageMetadata,
        array $rawChunks,
        array $contextData,
        bool $isVoiceEnabled
    ): array {
        $audioUsage = null;
        $audioData = null;

        // 1. Audio Generation (Optional)
        if ($isVoiceEnabled && !empty($fullText)) {
            $audioResult = $this->generateSpeech($fullText);
            if ($audioResult['status']) {
                $audioUsage = $audioResult['usage'] ?? null;
                $audioData = $audioResult['audioData'];
            }
        }

        $costData = ['costKSH' => 0];

        // 2. Transaction: Billing & Memory
        $this->db->transStart();

        // Calculate & Deduct Cost
        if ($usageMetadata) {
            $costData = $this->calculateCost($usageMetadata, $audioUsage);
            $deduction = number_format($costData['costKSH'], 4, '.', '');
            $this->userModel->deductBalance($userId, $deduction);
        }

        // Update Memory
        if (isset($contextData['memoryService'])) {
            $contextData['memoryService']->updateMemory(
                $inputText,
                $fullText,
                $rawChunks,
                $contextData['usedInteractionIds'] ?? []
            );
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            log_message('error', "[GeminiService] Transaction failed for User ID: {$userId}");
            // We don't throw here to avoid crashing the stream close, but we log critically
        }

        return [
            'costKSH' => $costData['costKSH'],
            'audioData' => $audioData
        ];
    }
    /**
     * Stores a temporary file for Gemini multimodal context.
     *
     * @param \CodeIgniter\HTTP\Files\UploadedFile $file
     * @param int $userId
     * @return array [status => bool, filename => string, error => string|null]
     */
    public function storeTempMedia($file, int $userId): array
    {
        $userTempPath = WRITEPATH . 'uploads/gemini_temp/' . $userId . '/';

        if (!is_dir($userTempPath)) {
            if (!mkdir($userTempPath, 0755, true)) {
                return ['status' => false, 'error' => 'Failed to create directory.'];
            }
        }

        $fileName = $file->getRandomName();
        if (!$file->move($userTempPath, $fileName)) {
            return ['status' => false, 'error' => $file->getErrorString()];
        }

        return ['status' => true, 'filename' => $fileName, 'original_name' => $file->getClientName()];
    }
    /**
     * Processing uploaded files for Gemini API.
     *
     * @param array $fileIds
     * @param int $userId
     * @return array
     */
    public function prepareUploadedFiles(array $fileIds, int $userId): array
    {
        $parts = [];
        $userTempPath = WRITEPATH . 'uploads/gemini_temp/' . $userId . '/';
        // Check for supported mime types - using centralized constant
        $supportedMimeTypes = self::SUPPORTED_MIME_TYPES;

        foreach ($fileIds as $fileId) {
            $filePath = $userTempPath . basename($fileId);

            if (!file_exists($filePath)) {
                return ['error' => "File not found. Please upload again."];
            }

            $mimeType = mime_content_type($filePath);
            if (!in_array($mimeType, $supportedMimeTypes, true)) {
                return ['error' => "Unsupported file type."];
            }

            $parts[] = ['inlineData' => [
                'mimeType' => $mimeType,
                'data' => base64_encode(file_get_contents($filePath))
            ]];
        }
        return ['parts' => $parts];
    }

    /**
     * Cleans up temporary files after processing.
     *
     * @param array $fileIds
     * @param int $userId
     * @return void
     */
    public function cleanupTempFiles(array $fileIds, int $userId): void
    {
        foreach ($fileIds as $fileId) {
            @unlink(WRITEPATH . 'uploads/gemini_temp/' . $userId . '/' . basename($fileId));
        }
    }

    /**
     * Processes raw audio data into a file for serving.
     *
     * @param string $base64Data
     * @param int $userId
     * @return string|null Filename
     */
    public function processAudioForServing(string $base64Data, int $userId): ?string
    {
        $securePath = WRITEPATH . 'uploads/ttsaudio_secure/' . $userId . '/';

        if (!is_dir($securePath)) {
            mkdir($securePath, 0755, true);
        }

        $filenameBase = 'speech_' . bin2hex(random_bytes(8));

        // Use the injected service
        $result = $this->ffmpegService->processAudio(
            $base64Data,
            $securePath,
            $filenameBase
        );

        if (!$result['success'] || !$result['fileName']) {
            return null;
        }

        return $result['fileName'];
    }
    /**
     * Retrieves user settings.
     *
     * @param int $userId
     * @return object|null
     */
    public function getUserSettings(int $userId)
    {
        return $this->userSettingsModel->where('user_id', $userId)->first();
    }

    /**
     * Updates or creates a user setting.
     *
     * @param int $userId
     * @param string $key
     * @param bool $value
     * @return bool
     */
    public function updateUserSetting(int $userId, string $key, bool $value): bool
    {
        $setting = $this->getUserSettings($userId);

        if ($setting) {
            return $this->userSettingsModel->update($setting->id, [$key => $value]);
        }

        return (bool) $this->userSettingsModel->save([
            'user_id' => $userId,
            $key      => $value
        ]);
    }

    /**
     * Retrieves user prompts.
     *
     * @param int $userId
     * @return array
     */
    public function getUserPrompts(int $userId): array
    {
        return $this->promptModel->where('user_id', $userId)->findAll();
    }

    /**
     * Adds a new prompt.
     *
     * @param int $userId
     * @param array $data
     * @return int|bool Insert ID or false
     */
    public function addPrompt(int $userId, array $data)
    {
        return $this->promptModel->insert([
            'user_id'     => $userId,
            'title'       => $data['title'],
            'prompt_text' => $data['prompt_text']
        ]);
    }

    /**
     * Deletes a prompt if it belongs to the user.
     *
     * @param int $userId
     * @param int $promptId
     * @return bool
     */
    public function deletePrompt(int $userId, int $promptId): bool
    {
        $prompt = $this->promptModel->find($promptId);
        if ($prompt && $prompt->user_id == $userId) {
            return $this->promptModel->delete($promptId);
        }
        return false;
    }

    /**
     * Deletes a specific temporary media file.
     *
     * @param int $userId
     * @param string $fileId
     * @return bool
     */
    public function deleteTempMedia(int $userId, string $fileId): bool
    {
        $filePath = WRITEPATH . 'uploads/gemini_temp/' . $userId . '/' . basename($fileId);
        if (file_exists($filePath) && unlink($filePath)) {
            return true;
        }
        return false;
    }

    /**
     * Resolves the path for a served audio file.
     *
     * @param int $userId
     * @param string $fileName
     * @return string|null Absolute path or null if not found
     */
    public function getAudioFilePath(int $userId, string $fileName): ?string
    {
        $path = WRITEPATH . 'uploads/ttsaudio_secure/' . $userId . '/' . basename($fileName);
        return file_exists($path) ? $path : null;
    }
}
