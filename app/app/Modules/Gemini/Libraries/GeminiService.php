<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use App\Modules\Gemini\Libraries\ModelPayloadService;
use CodeIgniter\I18n\Time;
use App\Models\UserModel;

/**
 * Service layer for interacting with the Google Gemini API.
 *
 * This service handles:
 * - Text generation using various Gemini models.
 * - Text-to-Speech (TTS) generation.
 * - Token counting and cost estimation.
 * - Cost calculation based on usage metadata.
 * - Payload configuration management via ModelPayloadService.
 */
class GeminiService
{
    /**
     * The API key for authenticating with the Gemini API.
     * @var string|null
     */
    protected $apiKey;

    /**
     * Reference to the payload configuration service.
     * @var ModelPayloadService
     */
    protected $payloadService;

    /**
     * An ordered list of Gemini model IDs to try, from most preferred to least preferred.
     * This fallback mechanism ensures high availability even if specific models are rate-limited.
     *
     * @var array<string>
     */
    public const MODEL_PRIORITIES = [
        "gemini-3-pro-preview", // Multimodal, Thinking Level High
        "gemini-2.5-pro",       // Multimodal, Thinking Budget
        "gemini-flash-latest",      // Primary: Latest Flash model for speed and efficiency
        "gemini-flash-lite-latest", // Secondary: Lite version for lower latency
        "gemini-2.5-flash",         // Fallback: Stable Flash version
        "gemini-2.5-flash-lite",    // Fallback: Stable Lite version
        "gemini-2.0-flash",         // Legacy Fallback
        "gemini-2.0-flash-lite",    // Legacy Fallback
    ];

    /**
     * Reference to the User Model for balance updates.
     * @var \App\Models\UserModel
     */
    protected $userModel;

    /**
     * Database connection.
     * @var \CodeIgniter\Database\BaseConnection
     */
    protected $db;

    /**
     * Pricing configuration for text and audio models (per 1 Million tokens).
     *
     * Text Pricing:
     * - Tier 1 (<= 200k tokens): Lower rate for standard context windows.
     * - Tier 2 (> 200k tokens): Higher rate for long-context processing.
     *
     * Audio Pricing:
     * - Fixed rate for input and output audio tokens.
     */
    public const PRICING_CONFIG = [
        'default' => [
            'tier1' => ['input' => 2.00, 'output' => 12.00],
            'tier2' => ['input' => 4.00, 'output' => 18.00],
            'tier_threshold' => 200000
        ],
        'audio' => [
            'input' => 0.50,
            'output' => 10.00
        ]
    ];

    /**
     * Constructor.
     * Initializes dependencies and loads the API key from the environment.
     */
    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY') ?? getenv('GEMINI_API_KEY');
        $this->payloadService = service('modelPayloadService');
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Encapsulates the entire interaction logic: Context, Cost, Generation, TTS, and Transactions.
     * 
     * @param int $userId
     * @param string $prompt
     * @param array $fileParts
     * @param array $options ['assistant_mode' => bool, 'voice_mode' => bool]
     * @return array
     */
    public function processInteraction(int $userId, string $prompt, array $fileParts, array $options): array
    {
        $isAssistantMode = $options['assistant_mode'] ?? true;
        $isVoiceMode = $options['voice_mode'] ?? false;

        $contextData = ['finalPrompt' => $prompt, 'memoryService' => null, 'usedInteractionIds' => []];

        // 1. Context Preparation
        if ($isAssistantMode && !empty(trim($prompt))) {
            $memoryService = service('memory', $userId);
            $recalled = $memoryService->getRelevantContext($prompt);

            $template = $memoryService->getTimeAwareSystemPrompt();
            $template = str_replace('{{CURRENT_TIME}}', Time::now()->format('Y-m-d H:i:s T'), $template);
            $template = str_replace('{{CONTEXT_FROM_MEMORY_SERVICE}}', $recalled['context'], $template);
            $template = str_replace('{{USER_QUERY}}', htmlspecialchars($prompt), $template);
            $template = str_replace('{{TONE_INSTRUCTION}}', "Maintain default persona: dry, witty, concise.", $template);

            $contextData['finalPrompt'] = $template;
            $contextData['memoryService'] = $memoryService;
            $contextData['usedInteractionIds'] = $recalled['used_interaction_ids'];
        }

        $allParts = $fileParts;
        if ($contextData['finalPrompt']) {
            array_unshift($allParts, ['text' => $contextData['finalPrompt']]);
        }

        if (empty($allParts)) {
            return ['error' => 'Please provide a prompt or file.'];
        }

        // 2. Cost Estimation & Balance Check
        $user = $this->userModel->find($userId);
        $estimate = $this->estimateCost($allParts);

        if ($estimate['status'] && $user->balance < $estimate['costKSH']) {
            return ['error' => "Insufficient balance. Estimated Input Cost: KSH " . number_format($estimate['costKSH'], 2)];
        }

        // 3. API Execution
        $apiResponse = $this->generateContent($allParts);

        if (isset($apiResponse['error'])) {
            return ['error' => $apiResponse['error']];
        }

        // 4. TTS Execution
        $audioResult = null;
        if ($isVoiceMode && !empty(trim($apiResponse['result']))) {
            $speech = $this->generateSpeech($apiResponse['result']);
            if ($speech['status']) {
                $audioResult = $speech; // Contains audioData and usage
            }
        }

        // 5. Transactional Write (Balance + Memory)
        $costKSH = 0.0;
        $this->db->transStart();

        // Calculate & Deduct Cost
        if (isset($apiResponse['usage']) || ($audioResult['usage'] ?? null)) {
            $textUsage = $apiResponse['usage'] ?? [];
            $audioUsage = $audioResult['usage'] ?? null;

            $costData = $this->calculateCost($textUsage, $audioUsage);
            $costKSH = $costData['costKSH'];
            $deduction = number_format($costKSH, 4, '.', '');

            $this->userModel->deductBalance($userId, $deduction);
        }

        // Update Memory
        // Update Memory
        $memoryService = $contextData['memoryService'] ?? null;
        if ($isAssistantMode && $memoryService) {
            $memoryService->updateMemory(
                $prompt,
                $apiResponse['result'],
                $contextData['usedInteractionIds']
            );
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['error' => 'Transaction failed. Please try again.'];
        }

        return [
            'result' => $apiResponse['result'],
            'costKSH' => $costKSH,
            'audioData' => $audioResult['audioData'] ?? null,
            'success' => true
        ];
    }

    /**
     * Sends a request to the Gemini API for text generation.
     *
     * Iterates through the configured model priorities. If a model fails due to
     * quota limits (429), it automatically retries with exponential backoff
     * or fails over to the next available model.
     *
     * @param array $parts An array of content parts (text, images, files).
     * @return array An associative array containing:
     *               - 'result' (string): The generated text.
     *               - 'usage' (array|null): Token usage metadata.
     *               - 'error' (string): Error message if the operation failed.
     */
    public function generateContent(array $parts): array
    {
        if (!$this->apiKey) {
            return ['error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $apiKey = trim($this->apiKey);
        $lastError = ['error' => 'An unexpected error occurred after multiple retries.'];

        if (empty(self::MODEL_PRIORITIES)) {
            return ['error' => 'No Gemini models configured in modelPriorities.'];
        }

        foreach (self::MODEL_PRIORITIES as $model) {
            $currentModel = $model;

            // Retrieve the specific payload configuration for the current model
            $config = $this->payloadService->getPayloadConfig($currentModel, $apiKey, $parts);

            // Skip if no valid configuration exists for this model
            if (empty($config)) {
                log_message('warning', "GeminiService: Skipping model '$currentModel' because no payload configuration was found.");
                continue;
            }

            $apiUrl = $config['url'];
            $requestBody = $config['body'];

            $maxRetries = 3;
            $initialDelay = 1;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $client = \Config\Services::curlrequest();
                    $response = $client->request('POST', $apiUrl, [
                        'body' => $requestBody,
                        'headers' => ['Content-Type' => 'application/json'],
                        'timeout' => 90,
                        'connect_timeout' => 15,
                    ]);

                    $statusCode = $response->getStatusCode();
                    $responseBody = $response->getBody();

                    // Handle Rate Limiting (Quota Exceeded)
                    if ($statusCode === 429) {
                        log_message('warning', "Gemini API Quota Exceeded (429) for model '{$currentModel}' on attempt {$attempt}.");
                        $lastError = ['error' => "Quota exceeded for model '{$currentModel}'."];
                        if ($attempt < $maxRetries) {
                            sleep($initialDelay * pow(2, $attempt - 1)); // Exponential backoff
                            continue;
                        } else {
                            break; // Try next model
                        }
                    }

                    // Handle General API Errors
                    if ($statusCode !== 200) {
                        $errorData = json_decode($responseBody, true);
                        $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                        log_message('error', "Gemini API Error: Status {$statusCode} - {$errorMessage} | Model: {$currentModel} | Response: {$responseBody}");
                        return ['error' => $errorMessage];
                    }

                    $responseData = json_decode($responseBody, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        log_message('error', 'Gemini API Response JSON Decode Error: ' . json_last_error_msg() . ' | Response: ' . $responseBody);
                        return ['error' => 'Failed to decode API response.'];
                    }

                    // Extract Generated Text
                    $processedText = '';
                    if (isset($responseData['candidates'][0]['content']['parts'])) {
                        foreach ($responseData['candidates'][0]['content']['parts'] as $part) {
                            $processedText .= $part['text'] ?? '';
                        }
                    }

                    $usageMetadata = $responseData['usageMetadata'] ?? null;

                    if (empty($processedText) && $usageMetadata === null) {
                        return ['error' => 'Received an empty or invalid response from the AI.'];
                    }

                    return ['result' => $processedText, 'usage' => $usageMetadata];
                } catch (\Exception $e) {
                    log_message('error', "Gemini API Request Attempt {$attempt} failed for model '{$currentModel}': " . $e->getMessage());
                    $lastError = ['error' => 'The AI service is currently unavailable or the request timed out. Please try again in a few moments.'];
                    if ($attempt < $maxRetries) {
                        sleep($initialDelay * pow(2, $attempt - 1));
                    }
                }
            }
        }

        // Final error handling if all models fail
        $finalErrorMsg = $lastError['error'] ?? 'An unexpected error occurred after multiple retries across all models.';
        if (str_contains($finalErrorMsg, 'Quota exceeded')) {
            return ['error' => 'All available AI models have exceeded their quota. Please wait and try again later.'];
        }

        return $lastError;
    }

    /**
     * Generates raw PCM audio data from a text string using the Gemini TTS API.
     *
     * Uses the 'gemini-2.5-flash-preview-tts' model to synthesize speech.
     * The output is raw audio data that needs to be processed (e.g., by FFmpeg)
     * before being played in a browser.
     *
     * @param string $textToSpeak The text content to convert to speech.
     * @return array An associative array containing:
     *               - 'status' (bool): Success status.
     *               - 'audioData' (string|null): Base64 encoded raw audio data.
     *               - 'usage' (array|null): Token usage metadata for the audio generation.
     *               - 'error' (string|null): Error message on failure.
     */
    public function generateSpeech(string $textToSpeak): array
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
                ["role" => "user", "parts" => [["text" => $textToSpeak]]]
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
     * Counts the number of tokens in the provided content parts.
     *
     * Useful for pre-request validation and cost estimation.
     *
     * @param array $parts The content parts to count tokens for.
     * @return array ['status' => bool, 'totalTokens' => int, 'error' => string|null]
     */
    public function countTokens(array $parts): array
    {
        $apiKey = trim($this->apiKey);
        if (!$apiKey) {
            return ['status' => false, 'error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $currentModel = "gemini-2.0-flash"; // Default model for token counting
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

    /**
     * Estimates the cost of a request based on input tokens.
     *
     * This method is used by the controller to check if the user has sufficient balance
     * *before* making the actual generation request. It uses Tier 1 pricing for a conservative estimate.
     *
     * @param array $parts The content parts to estimate.
     * @return array ['status' => bool, 'costKSH' => float, 'totalTokens' => int, 'error' => string]
     */
    public function estimateCost(array $parts): array
    {
        $response = $this->countTokens($parts);
        if (!$response['status']) {
            return ['status' => false, 'error' => $response['error']];
        }

        $estimatedTokens = $response['totalTokens'];

        // Use Tier 1 pricing for estimation (conservative approach)
        $pricing = self::PRICING_CONFIG['default']['tier1'];

        $estimatedCostUSD = ($estimatedTokens / 1000000) * $pricing['input'];
        $usdToKsh = 129; // Fixed exchange rate
        $estimatedCostKSH = $estimatedCostUSD * $usdToKsh;

        return [
            'status' => true,
            'costKSH' => $estimatedCostKSH,
            'totalTokens' => $estimatedTokens
        ];
    }

    /**
     * Calculates the actual cost based on usage metadata for text and optional audio.
     *
     * This method aggregates costs from both text generation (input/output tokens)
     * and audio generation (if applicable). It handles tiered pricing for text.
     *
     * @param array $textUsage Usage metadata from the text generation API.
     * @param array|null $audioUsage Usage metadata from the audio generation API (optional).
     * @return array An associative array containing:
     *               - 'costUSD' (float): Total cost in USD.
     *               - 'costKSH' (float): Total cost in KSH.
     *               - 'tokens' (int): Total combined tokens.
     *               - 'promptTokens' (int): Text prompt tokens.
     *               - 'candidatesTokens' (int): Text output tokens.
     */
    public function calculateCost(array $textUsage, ?array $audioUsage = null): array
    {
        // 1. Calculate Text Generation Cost
        $promptTokens = $textUsage['promptTokenCount'] ?? 0;
        $candidatesTokens = $textUsage['candidatesTokenCount'] ?? 0;
        $totalTextTokens = $textUsage['totalTokenCount'] ?? ($promptTokens + $candidatesTokens);

        $pricing = self::PRICING_CONFIG['default'];
        // Determine pricing tier based on total text tokens
        $tier = ($totalTextTokens > $pricing['tier_threshold']) ? 'tier2' : 'tier1';
        $rates = $pricing[$tier];

        $textInputCost = ($promptTokens / 1000000) * $rates['input'];
        $textOutputCost = ($candidatesTokens / 1000000) * $rates['output'];
        $totalTextCostUSD = $textInputCost + $textOutputCost;

        // 2. Calculate Audio Generation Cost (if applicable)
        $totalAudioCostUSD = 0;
        $audioTokens = 0;

        if ($audioUsage) {
            $audioInputTokens = $audioUsage['promptTokenCount'] ?? 0;
            $audioOutputTokens = $audioUsage['candidatesTokenCount'] ?? 0;
            $audioTokens = $audioUsage['totalTokenCount'] ?? ($audioInputTokens + $audioOutputTokens);

            $audioPricing = self::PRICING_CONFIG['audio'];
            $audioInputCost = ($audioInputTokens / 1000000) * $audioPricing['input'];
            $audioOutputCost = ($audioOutputTokens / 1000000) * $audioPricing['output'];
            $totalAudioCostUSD = $audioInputCost + $audioOutputCost;
        }

        // 3. Aggregate Total Costs
        $totalCostUSD = $totalTextCostUSD + $totalAudioCostUSD;
        $usdToKsh = 129;
        $totalCostKsh = $totalCostUSD * $usdToKsh;
        $totalTokens = $totalTextTokens + $audioTokens;

        return [
            'costUSD' => $totalCostUSD,
            'costKSH' => $totalCostKsh,
            'tokens' => $totalTokens,
            'promptTokens' => $promptTokens,
            'candidatesTokens' => $candidatesTokens
        ];
    }
    /**
     * Streams text generation from the Gemini API using Server-Sent Events (SSE).
     *
     * @param array $parts An array of content parts.
     * @param callable $chunkCallback Function to call with each text chunk: function(string $text)
     * @param callable $completeCallback Function to call on completion: function(string $fullText, ?array $metadata)
     * @return void
     */
    public function generateStream(array $parts, callable $chunkCallback, callable $completeCallback): void
    {
        if (!$this->apiKey) {
            $chunkCallback("Error: GEMINI_API_KEY not set.");
            return;
        }

        $apiKey = trim($this->apiKey);
        $fullGeneratedText = '';
        $finalUsageMetadata = null;

        if (empty(self::MODEL_PRIORITIES)) {
            $chunkCallback("Error: No models configured.");
            return;
        }

        foreach (self::MODEL_PRIORITIES as $model) {
            $currentModel = $model;
            // Get Stream Config (note true for isStream)
            $config = $this->payloadService->getPayloadConfig($currentModel, $apiKey, $parts, true);

            // Skip if no payload config found ensuring we dont send bad requests
            if (empty($config)) continue;

            $apiUrl = $config['url'];
            $requestBody = $config['body'];

            // State for the write function
            $buffer = '';

            // We need a custom cURL handle to use CURLOPT_WRITEFUNCTION properly with keeping state
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Important: We handle output manually
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Longer timeout for streaming

            // The Write Function
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$buffer, &$fullGeneratedText, &$finalUsageMetadata, $chunkCallback) {
                $buffer .= $chunk;

                // Google sends a JSON array stream: [{...}, \n {...}]
                // We need to parse valid objects out of this stream.
                // This is a simple parser that looks for balanced braces.

                // Continue parsing while we can find a potential JSON object
                while (true) {
                    $buffer = trim($buffer);
                    if (empty($buffer)) break;

                    // Strip leading comma or array bracket if present from previous chunk remnants
                    if (str_starts_with($buffer, ',') || str_starts_with($buffer, '[')) {
                        $buffer = trim(substr($buffer, 1));
                        continue;
                    }

                    if (str_starts_with($buffer, ']')) {
                        // End of stream array
                        $buffer = trim(substr($buffer, 1));
                        continue;
                    }

                    // Check if we have a full object via brace counting
                    if (str_starts_with($buffer, '{')) {
                        $openBraces = 0;
                        $endPos = -1;
                        $inString = false;
                        $escaped = false;

                        $len = strlen($buffer);
                        for ($i = 0; $i < $len; $i++) {
                            $char = $buffer[$i];

                            if (!$inString) {
                                if ($char === '{') $openBraces++;
                                elseif ($char === '}') {
                                    $openBraces--;
                                    if ($openBraces === 0) {
                                        $endPos = $i;
                                        break;
                                    }
                                } elseif ($char === '"') $inString = true;
                            } else {
                                if ($char === '\\' && !$escaped) {
                                    $escaped = true;
                                } elseif ($char === '"' && !$escaped) {
                                    $inString = false;
                                } else {
                                    $escaped = false;
                                }
                            }
                        }

                        if ($endPos !== -1) {
                            // We found a complete object
                            $jsonStr = substr($buffer, 0, $endPos + 1);
                            $remaining = substr($buffer, $endPos + 1);

                            $data = json_decode($jsonStr, true);

                            if (json_last_error() === JSON_ERROR_NONE) {
                                // Process Valid Object
                                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                                    $textChunk = $data['candidates'][0]['content']['parts'][0]['text'];
                                    $fullGeneratedText .= $textChunk;
                                    $chunkCallback($textChunk);
                                }

                                if (isset($data['usageMetadata'])) {
                                    $finalUsageMetadata = $data['usageMetadata'];
                                }

                                // Advance buffer
                                $buffer = $remaining;
                                continue;
                            } else {
                                // Logic error or malformed JSON, strip it to avoid infinite loop
                                $buffer = $remaining;
                            }
                        } else {
                            // Incomplete object, wait for more data
                            break;
                        }
                    } else {
                        // Garbage or unhandled format, skip one char
                        if (strlen($buffer) > 0) {
                            $buffer = substr($buffer, 1);
                        } else {
                            break;
                        }
                    }
                }

                return strlen($chunk);
            });

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            // curl_close($ch); // Deprecated/Not needed in PHP 8+, auto-closed

            if ($httpCode === 200) { // Success even if result is bool(true) content was handled by writefunction
                $completeCallback($fullGeneratedText, $finalUsageMetadata);
                return;
            }

            // If we are here, it failed.
            if ($httpCode === 429) {
                log_message('warning', "Gemini Streaming Quota Exceeded (429) for model '{$currentModel}'");
                // Continue to next model
            } else {
                log_message('error', "Gemini Streaming Error: Status {$httpCode} - {$curlError}");
            }
        }

        // If we reach here, all models failed
        $chunkCallback("Error: Unable to generate stream. Please try again later.");
    }
}
