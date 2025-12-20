<?php

declare(strict_types=1);

namespace App\Modules\Ollama\Libraries;

use App\Modules\Ollama\Config\Ollama as OllamaConfig;
use CodeIgniter\HTTP\CURLRequest;
use Config\Services;

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
 *
 * @package App\Modules\Ollama\Libraries
 */
class OllamaService
{
    /**
     * Constructor with Partial Property Promotion (PHP 8.0+)
     *
     * Note: We cannot use function calls (new, config(), Services::x()) as default
     * values in constructor parameters (PHP limitation). Therefore, we:
     * 1. Accept nullable parameters with null defaults
     * 2. Use null coalescing operator (??) in the constructor body to instantiate
     *
     * This pattern maintains property promotion benefits while working within PHP's constraints.
     *
     * @param OllamaConfig|null $config Configuration object (auto-instantiated if null)
     * @param OllamaPayloadService|null $payloadService Payload builder (auto-instantiated if null)
     * @param CURLRequest|null $client HTTP client for API requests (auto-instantiated if null)
     */
    public function __construct(
        protected ?OllamaConfig $config = null,
        protected ?OllamaPayloadService $payloadService = null,
        protected ?CURLRequest $client = null
    ) {
        // Initialize dependencies with defaults if not provided (Dependency Injection pattern)
        $this->config = $config ?? new OllamaConfig();
        $this->payloadService = $payloadService ?? new OllamaPayloadService();
        $this->client = $client ?? Services::curlrequest([
            'timeout' => $this->config->timeout,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Check Ollama Server Connection
     *
     * Performs a health check by sending a GET request to the Ollama server base URL.
     * Used to verify server availability before making API calls.
     *
     * @return array Status array with 'status', 'message', and 'data' keys
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
                'trace' => $e->getTraceAsString()
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
     *
     * Fetches the list of installed models from the Ollama server.
     * Returns model names in a standardized response structure.
     *
     * @return array Status array with 'status', 'message', and 'data' (array of model names)
     */
    public function getModels(): array
    {
        try {
            $url = rtrim($this->config->baseUrl, '/') . '/api/tags';
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== 200) {
                log_message('error', 'Ollama Get Models Failed - Non-200 Status', [
                    'status_code' => $response->getStatusCode(),
                    'url' => $url,
                    'response_body' => $response->getBody()
                ]);
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
            log_message('error', 'Ollama Get Models Failed', [
                'exception' => $e->getMessage(),
                'url' => $url ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'status' => 'error',
                'message' => 'Failed to connect to Ollama server',
                'data' => []
            ];
        }
    }

    /**
     * Generate Chat Completion (Synchronous)
     *
     * Sends a chat request to Ollama and waits for the complete response.
     * Supports multimodal input (text + images) for vision-capable models.
     *
     * @param string $model Model identifier (e.g., 'llama3', 'mistral')
     * @param array $messages Array of message objects with 'role' and 'content' keys
     * @return array Response with 'status', 'message', and 'data' (containing 'result' and 'usage')
     */
    public function generateChat(string $model, array $messages): array
    {
        // Input validation
        if (empty($model)) {
            return [
                'status' => 'error',
                'message' => 'Model name cannot be empty',
                'data' => null
            ];
        }

        if (empty($messages)) {
            return [
                'status' => 'error',
                'message' => 'Messages array cannot be empty',
                'data' => null
            ];
        }

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
                log_message('error', 'Ollama API Error', [
                    'status_code' => $statusCode,
                    'error' => $error,
                    'model' => $model,
                    'url' => $config['url']
                ]);
                return [
                    'status' => 'error',
                    'message' => "Ollama Error: {$error}",
                    'data' => null
                ];
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
                            'load_duration' => $data['load_duration'] ?? 0,
                            'prompt_eval_count' => $data['prompt_eval_count'] ?? 0,
                            'eval_count' => $data['eval_count'] ?? 0,
                        ]
                    ]
                ];
            }

            log_message('error', 'Ollama Invalid Response Format', [
                'model' => $model,
                'response_keys' => array_keys($data)
            ]);
            return [
                'status' => 'error',
                'message' => 'Invalid response format from Ollama',
                'data' => null
            ];
        } catch (\Exception $e) {
            log_message('error', 'Ollama Generate Failed', [
                'exception' => $e->getMessage(),
                'model' => $model,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'status' => 'error',
                'message' => 'Failed to connect to Ollama. Is it running?',
                'data' => null
            ];
        }
    }

    /**
     * Generate Chat Completion with Streaming (SSE)
     *
     * Streams chat response chunks in real-time using Server-Sent Events.
     * Each chunk is passed to the callback function as it arrives.
     *
     * @param string $model Model identifier (e.g., 'llama3', 'mistral')
     * @param array $messages Array of message objects with 'role' and 'content' keys
     * @param callable $callback Function to call for each chunk: function(string $chunk): void
     * @return array Response with 'status', 'message', and 'data' (containing 'usage' stats)
     */
    /**
     * Generate Chat Completion with Streaming (SSE)
     *
     * Streams chat response chunks in real-time using Server-Sent Events.
     * Mirrored from GeminiService structure to support controller callbacks.
     *
     * @param string $model Model identifier
     * @param array $messages Array of message objects
     * @param callable $chunkCallback Function(string|array $chunk): void
     * @param callable $completeCallback Function(string $fullText, ?array $usage): void
     * @return void
     */
    public function generateStream(string $model, array $messages, callable $chunkCallback, ?callable $completeCallback = null): void
    {
        // Default to no-op if completeCallback is missing (backward compatibility protection)
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
                    // Ollama sends JSON objects line by line (ndjson)
                    // We process the buffer to extract complete lines
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
                                    'load_duration' => $data['load_duration'] ?? 0,
                                    'prompt_eval_count' => $data['prompt_eval_count'] ?? 0,
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
                // If 404/500, the last line in buffer might contain the error if not processed
                // But usually we've handled errors in the loop if they were proper JSON.
                // If it's a raw HTML error page, we might need to handle it.
                $chunkCallback(['error' => "Ollama API returned status {$statusCode}"]);
                return;
            }

            // Stream finished successfully
            $completeCallback($fullText, $usage);
        } catch (\Throwable $e) {
            log_message('error', 'Ollama Stream Failed', [
                'exception' => $e->getMessage(),
                'model' => $model
            ]);
            $chunkCallback(['error' => 'Streaming failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Chat Wrapper Method (Legacy Compatibility)
     *
     * Convenience wrapper around generateChat() with optional model parameter.
     * Uses the default model from config if not specified.
     *
     * @param array $messages Array of message objects with 'role' and 'content' keys
     * @param string|null $model Optional model identifier (uses default if null)
     * @return array Response with 'status', 'message', and 'data' (containing 'response', 'model', 'usage')
     */
    public function chat(array $messages, ?string $model = null): array
    {
        // Input validation
        if (empty($messages)) {
            return [
                'status' => 'error',
                'message' => 'Messages array cannot be empty',
                'data' => null
            ];
        }

        $model = $model ?? $this->config->defaultModel;
        $response = $this->generateChat($model, $messages);

        // generateChat now returns standardized format
        if ($response['status'] === 'error') {
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
     *
     * Converts text input into a dense vector representation using the configured
     * embedding model. Used for semantic search and memory retrieval.
     *
     * @param string $input Text to convert into embedding vector
     * @return array Response with 'status', 'message', and 'data' (array of floats or empty on error)
     */
    public function embed(string $input): array
    {
        // Input validation
        if (empty($input)) {
            return [
                'status' => 'error',
                'message' => 'Input text cannot be empty',
                'data' => []
            ];
        }

        $url = rtrim($this->config->baseUrl, '/') . '/api/embed';

        $payload = [
            'model'  => $this->config->embeddingModel,
            'input'  => $input
        ];

        try {
            log_message('info', 'Ollama Embed Request', [
                'model' => $this->config->embeddingModel,
                'input_length' => strlen($input)
            ]);

            $response = $this->client->post($url, [
                'body'        => json_encode($payload),
                'headers'     => ['Content-Type' => 'application/json'],
                'http_errors' => false
            ]);

            if ($response->getStatusCode() !== 200) {
                log_message('error', 'Ollama Embed Error', [
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $response->getBody(),
                    'model' => $this->config->embeddingModel
                ]);
                return [
                    'status' => 'error',
                    'message' => 'Failed to generate embeddings',
                    'data' => []
                ];
            }

            $data = json_decode($response->getBody(), true);

            $embedding = [];
            if (isset($data['embeddings']) && is_array($data['embeddings'])) {
                $embedding = $data['embeddings'][0] ?? [];
            } elseif (isset($data['embedding'])) {
                $embedding = $data['embedding'];
            }

            if (empty($embedding)) {
                log_message('error', 'Ollama Embed Empty Response', [
                    'response_keys' => array_keys($data),
                    'model' => $this->config->embeddingModel
                ]);
                return [
                    'status' => 'error',
                    'message' => 'Received empty embedding from server',
                    'data' => []
                ];
            }

            log_message('info', 'Ollama Embed Success', [
                'vector_size' => count($embedding),
                'model' => $this->config->embeddingModel
            ]);

            return [
                'status' => 'success',
                'message' => 'Embedding generated successfully',
                'data' => $embedding
            ];
        } catch (\Exception $e) {
            log_message('error', 'Ollama Embed Failed', [
                'exception' => $e->getMessage(),
                'model' => $this->config->embeddingModel,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'status' => 'error',
                'message' => 'Failed to connect to Ollama server',
                'data' => []
            ];
        }
    }
    /**
     * Finalizes the streaming interaction by handling billing and memory updates.
     * Use this to keep the Controller 'skinny'.
     */
    public function finalizeStreamInteraction(int $userId, string $inputText, string $fullText): array
    {
        $cost = 1.00; // Fixed cost for now

        $db = \Config\Database::connect();
        $userModel = new \App\Models\UserModel();

        // Transaction: Billing & Memory
        $db->transStart();

        // Deduct Cost
        $userModel->deductBalance($userId, (string)$cost);

        // Update Memory (future implementation if needed here, currently handled strictly by controller flow in some aspects, 
        // but ideally should be here. For now, we mirror the controller's logic which was just deduction).

        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('error', "[OllamaService] Transaction failed for User ID: {$userId}");
        }

        return [
            'cost' => $cost
        ];
    }

    /**
     * Stores a temporary file for Ollama multimodal context.
     *
     * @param \CodeIgniter\HTTP\Files\UploadedFile $file
     * @param int $userId
     * @return array [status => bool, filename => string, error => string|null]
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
}
