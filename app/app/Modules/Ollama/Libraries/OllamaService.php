<?php

declare(strict_types=1);

namespace App\Modules\Ollama\Libraries;

use App\Modules\Ollama\Config\Ollama as OllamaConfig;
use App\Modules\Ollama\Models\OllamaPromptModel;
use App\Modules\Ollama\Models\OllamaUserSettingsModel;
use CodeIgniter\HTTP\CURLRequest;
use Config\Services;
use App\Models\UserModel;

/**
 * Ollama Service
 *
 * Core service for communicating with a local Ollama server instance.
 * Handles all API interactions including model listing, text generation,
 * streaming responses, and vector embeddings for the memory system.
 *
 * Architecture:
 * - Uses OllamaPayloadService for request construction (separation of concerns)
 * - Supports both synchronous and streaming (SSE) generation modes
 * - Provides embeddings for hybrid memory retrieval system
 * - Manages user settings and prompts
 *
 * @package App\Modules\Ollama\Libraries
 */
class OllamaService
{
    public const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    public const SUPPORTED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/webp',
        'image/gif',
    ];

    /**
     * Constructor with Partial Property Promotion (PHP 8.0+)
     */
    public function __construct(
        protected ?OllamaConfig $config = null,
        protected ?OllamaPayloadService $payloadService = null,
        protected ?CURLRequest $client = null,
        protected ?UserModel $userModel = null,
        protected $db = null,
        protected ?OllamaPromptModel $promptModel = null,
        protected ?OllamaUserSettingsModel $userSettingsModel = null
    ) {
        // Initialize dependencies with defaults if not provided (Dependency Injection pattern)
        $this->config = $config ?? new OllamaConfig();
        $this->payloadService = $payloadService ?? new OllamaPayloadService();
        $this->client = $client ?? Services::curlrequest([
            'timeout' => $this->config->timeout,
            'connect_timeout' => 10,
        ]);
        $this->userModel = $userModel ?? new UserModel();
        $this->db = $db ?? \Config\Database::connect();
        $this->promptModel = $promptModel ?? new OllamaPromptModel();
        $this->userSettingsModel = $userSettingsModel ?? new OllamaUserSettingsModel();
    }

    /**
     * Centralized method to process a full User-AI interaction.
     * Handles balace checks, context building, file preparation, API call, and transaction persistence.
     */
    public function processInteraction(int $userId, string $prompt, array $uploadedFileIds, string $model, array $options = []): array
    {
        // 1. Balance Check
        $cost = 1.00; // Fixed cost for now
        $user = $this->userModel->find($userId);
        if (!$user || $user->balance < $cost) {
            return ['status' => 'error', 'message' => 'Insufficient balance.'];
        }

        // 2. Prepare Files
        $images = $this->prepareUploadedFiles($uploadedFileIds, $userId);

        // 3. Execution (Assistant vs Standard)
        $isAssistantMode = $options['assistant_mode'] ?? true;

        if ($isAssistantMode) {
            // Delegate to MemoryService for advanced context
            // Note: We instantiate here to avoid circular dependencies in constructor if MemoryService depends on OllamaService
            $memoryService = new \App\Modules\Ollama\Libraries\OllamaMemoryService($userId, null, null, null, $this);
            $response = $memoryService->processChat($prompt, $model, $images);
        } else {
            // Standard Stateless Chat
            $messages = [['role' => 'user', 'content' => $prompt]];
            if (!empty($images)) {
                $messages[0]['images'] = $images;
            }
            $response = $this->generateChat($model, $messages);
        }

        // 4. Handle Errors
        if (isset($response['status']) && $response['status'] === 'error') {
            return $response;
        }
        // Legacy error support from MemoryService
        if (isset($response['success']) && !$response['success']) {
            return ['status' => 'error', 'message' => $response['error'] ?? 'Unknown error'];
        }

        // 5. Transaction & Deduct Balance
        // Note: MemoryService already marks memory/interaction. We just need to handle the billing here if not done.
        // The previous controller logic deducted balance AFTER generation.
        // MemoryService::processChat -> calls api->chat -> but DOES NOT deduct balance.
        // MemoryService::processChat -> calls _saveInteraction to save history.

        $this->db->transStart();
        $this->userModel->deductBalance($userId, (string)$cost);
        $this->db->transComplete();

        // 6. Return Result
        // Normalize response
        $resultText = $response['data']['result'] ?? $response['response'] ?? $response['data']['response'] ?? '';
        $usage = $response['data']['usage'] ?? $response['usage'] ?? [];

        return [
            'status' => 'success',
            'result' => $resultText,
            'cost' => $cost,
            'usage' => $usage
        ];
    }

    /**
     * Check Ollama Server Connection
     */
    public function checkConnection(): array
    {
        try {
            $url = rtrim($this->config->baseUrl, '/') . '/';
            $response = $this->client->get($url);

            if ($response->getStatusCode() === 200) {
                return [
                    'status' => 'success',
                    'message' => 'Connection successful',
                    'data' => ['url' => $url]
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Server returned non-200 status',
                'data' => ['status_code' => $response->getStatusCode()]
            ];
        } catch (\Exception $e) {
            log_message('error', 'Ollama Connection Check Failed', [
                'exception' => $e->getMessage(),
                'url' => $url ?? $this->config->baseUrl,
            ]);
            return [
                'status' => 'error',
                'message' => 'Failed to connect to Ollama server',
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Get Available Models from Ollama Server
     */
    public function getModels(): array
    {
        try {
            $url = rtrim($this->config->baseUrl, '/') . '/api/tags';
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== 200) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to retrieve models from server',
                    'data' => []
                ];
            }

            $data = json_decode($response->getBody(), true);
            $models = [];

            if (isset($data['models']) && is_array($data['models'])) {
                foreach ($data['models'] as $model) {
                    $models[] = $model['name'];
                }
            }

            return [
                'status' => 'success',
                'message' => 'Models retrieved successfully',
                'data' => $models
            ];
        } catch (\Exception $e) {
            log_message('error', 'Ollama Get Models Failed', ['exception' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => 'Failed to connect to Ollama server',
                'data' => []
            ];
        }
    }

    /**
     * Generate Chat Completion (Synchronous)
     */
    public function generateChat(string $model, array $messages): array
    {
        if (empty($model)) return ['status' => 'error', 'message' => 'Model name cannot be empty'];
        if (empty($messages)) return ['status' => 'error', 'message' => 'Messages array cannot be empty'];

        $config = $this->payloadService->getPayloadConfig($model, $messages, false);

        try {
            $response = $this->client->post($config['url'], [
                'body' => $config['body'],
                'headers' => ['Content-Type' => 'application/json']
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody();

            if ($statusCode !== 200) {
                $error = json_decode($body, true)['error'] ?? 'Unknown API error';
                return ['status' => 'error', 'message' => "Ollama Error: {$error}"];
            }

            $data = json_decode($body, true);

            if (isset($data['message']['content'])) {
                return [
                    'status' => 'success',
                    'message' => 'Chat generated successfully',
                    'data' => [
                        'result' => $data['message']['content'],
                        'usage' => [
                            'total_duration' => $data['total_duration'] ?? 0,
                            'eval_count' => $data['eval_count'] ?? 0,
                        ]
                    ]
                ];
            }

            return ['status' => 'error', 'message' => 'Invalid response format from Ollama'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to connect to Ollama. Is it running?'];
        }
    }

    /**
     * Generate Chat Completion with Streaming (SSE)
     */
    public function generateStream(string $model, array $messages, callable $chunkCallback, ?callable $completeCallback = null): void
    {
        $completeCallback = $completeCallback ?? function () {};

        if (empty($model)) {
            $chunkCallback(['error' => 'Model name cannot be empty']);
            return;
        }

        $config = $this->payloadService->getPayloadConfig($model, $messages, true);

        $buffer = '';
        $fullText = '';
        $usage = null;

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $config['url'],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $config['body'],
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$buffer, &$fullText, &$usage, $chunkCallback) {
                    $buffer .= $chunk;
                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line = substr($buffer, 0, $pos);
                        $buffer = substr($buffer, $pos + 1);

                        $line = trim($line);
                        if (empty($line)) continue;

                        $data = json_decode($line, true);
                        if ($data) {
                            if (isset($data['message']['content'])) {
                                $text = $data['message']['content'];
                                $fullText .= $text;
                                $chunkCallback($text);
                            }
                            if (isset($data['done']) && $data['done'] === true) {
                                $usage = [
                                    'total_duration' => $data['total_duration'] ?? 0,
                                    'eval_count' => $data['eval_count'] ?? 0,
                                ];
                            }
                            if (isset($data['error'])) {
                                $chunkCallback(['error' => $data['error']]);
                            }
                        }
                    }
                    return strlen($chunk);
                }
            ]);

            curl_exec($ch);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                $chunkCallback(['error' => "Connection Error: $error"]);
                return;
            }

            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($statusCode !== 200) {
                $chunkCallback(['error' => "Ollama API returned status {$statusCode}"]);
                return;
            }

            $completeCallback($fullText, $usage);
        } catch (\Throwable $e) {
            $chunkCallback(['error' => 'Streaming failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Chat Wrapper Method (Legacy Compatibility)
     */
    public function chat(array $messages, ?string $model = null): array
    {
        $model = $model ?? $this->config->defaultModel;
        $response = $this->generateChat($model, $messages);

        if (isset($response['status']) && $response['status'] === 'error') {
            return $response;
        }

        return [
            'status'  => 'success',
            'message' => 'Chat completed successfully',
            'data' => [
                'response' => $response['data']['result'],
                'model'    => $model,
                'usage'    => $response['data']['usage'] ?? []
            ]
        ];
    }

    /**
     * Generate Vector Embeddings
     */
    public function embed(string $input): array
    {
        if (empty($input)) return ['status' => 'error', 'message' => 'Input text cannot be empty', 'data' => []];

        $url = rtrim($this->config->baseUrl, '/') . '/api/embed';
        $payload = ['model' => $this->config->embeddingModel, 'input' => $input];

        try {
            $response = $this->client->post($url, [
                'body'        => json_encode($payload),
                'headers'     => ['Content-Type' => 'application/json'],
                'http_errors' => false
            ]);

            if ($response->getStatusCode() !== 200) {
                return ['status' => 'error', 'message' => 'Failed to generate embeddings', 'data' => []];
            }

            $data = json_decode($response->getBody(), true);
            $embedding = [];
            if (isset($data['embeddings']) && is_array($data['embeddings'])) {
                $embedding = $data['embeddings'][0] ?? [];
            } elseif (isset($data['embedding'])) {
                $embedding = $data['embedding'];
            }

            if (empty($embedding)) {
                return ['status' => 'error', 'message' => 'Received empty embedding', 'data' => []];
            }

            return ['status' => 'success', 'data' => $embedding];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to connect to Ollama server', 'data' => []];
        }
    }

    /**
     * Finalizes the streaming interaction by handling billing.
     */
    public function finalizeStreamInteraction(int $userId, string $inputText, string $fullText): array
    {
        $cost = 1.00; // Fixed cost for now
        $this->db->transStart();
        $this->userModel->deductBalance($userId, (string)$cost);
        $this->db->transComplete();

        return ['cost' => $cost];
    }

    /**
     * Stores a temporary file for Ollama multimodal context.
     */
    public function storeTempMedia($file, int $userId): array
    {
        $userTempPath = WRITEPATH . 'uploads/ollama_temp/' . $userId . '/';

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
     * Process Uploaded Files - Reads and encodes, then deletes.
     */
    public function prepareUploadedFiles(array $fileIds, int $userId): array
    {
        $images = [];
        $userTempPath = WRITEPATH . 'uploads/ollama_temp/' . $userId . '/';

        foreach ($fileIds as $fileId) {
            $filePath = $userTempPath . basename($fileId);
            if (file_exists($filePath)) {
                $images[] = base64_encode(file_get_contents($filePath));
                @unlink($filePath);
            }
        }
        return $images;
    }

    /**
     * Cleanup (though currently handled in prepareUploadedFiles for single-pass validity, 
     * this is defined for standardizing interface or bulk cleanup if needed).
     */
    public function cleanupTempFiles(array $fileIds, int $userId): void
    {
        $userTempPath = WRITEPATH . 'uploads/ollama_temp/' . $userId . '/';
        foreach ($fileIds as $fileId) {
            @unlink($userTempPath . basename($fileId));
        }
    }

    // --- User Settings Management ---

    public function getUserSettings(int $userId)
    {
        return $this->userSettingsModel->where('user_id', $userId)->first();
    }

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

    // --- Prompt Management ---

    public function getUserPrompts(int $userId): array
    {
        return $this->promptModel->where('user_id', $userId)->findAll();
    }

    public function addPrompt(int $userId, array $data)
    {
        return $this->promptModel->insert([
            'user_id'     => $userId,
            'title'       => $data['title'],
            'prompt_text' => $data['prompt_text']
        ]);
    }

    public function deletePrompt(int $userId, int $promptId): bool
    {
        $prompt = $this->promptModel->find($promptId);
        if ($prompt && $prompt->user_id == $userId) {
            return $this->promptModel->delete($promptId);
        }
        return false;
    }
}
