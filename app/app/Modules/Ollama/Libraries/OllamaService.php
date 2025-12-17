<?php

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

    public function checkConnection(): bool
    {
        try {
            $url = rtrim($this->config->baseUrl, '/') . '/';
            $response = $this->client->get($url);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            log_message('error', 'Ollama Connection Check Failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getModels(): array
    {
        try {
            $url = rtrim($this->config->baseUrl, '/') . '/api/tags';
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = json_decode($response->getBody(), true);
            $models = [];

            if (isset($data['models']) && is_array($data['models'])) {
                foreach ($data['models'] as $model) {
                    $models[] = $model['name'];
                }
            }

            return $models;
        } catch (\Exception $e) {
            log_message('error', 'Ollama Get Models Failed: ' . $e->getMessage());
            return [];
        }
    }

    public function generateChat(string $model, array $messages): array
    {
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
                log_message('error', "Ollama API Error ({$statusCode}): {$error}");
                return ['error' => "Ollama Error: {$error}"];
            }

            $data = json_decode($body, true);

            if (isset($data['message']['content'])) {
                return [
                    'result' => $data['message']['content'],
                    'usage' => [
                        'total_duration' => $data['total_duration'] ?? 0,
                        'load_duration' => $data['load_duration'] ?? 0,
                        'prompt_eval_count' => $data['prompt_eval_count'] ?? 0,
                        'eval_count' => $data['eval_count'] ?? 0,
                    ]
                ];
            }

            return ['error' => 'Invalid response format from Ollama.'];
        } catch (\Exception $e) {
            log_message('error', 'Ollama Generate Failed: ' . $e->getMessage());
            return ['error' => 'Failed to connect to Ollama. Is it running?'];
        }
    }

    public function generateStream(string $model, array $messages, callable $callback): array
    {
        $config = $this->payloadService->getPayloadConfig($model, $messages, true);
        $usage = [];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $config['url']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $config['body']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use ($callback, &$usage) {
                $lines = explode("\n", $chunk);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    $data = json_decode($line, true);
                    if ($data) {
                        if (isset($data['message']['content'])) {
                            $callback($data['message']['content']);
                        }
                        if (isset($data['done']) && $data['done'] === true) {
                            $usage = [
                                'total_duration' => $data['total_duration'] ?? 0,
                                'load_duration' => $data['load_duration'] ?? 0,
                                'prompt_eval_count' => $data['prompt_eval_count'] ?? 0,
                                'eval_count' => $data['eval_count'] ?? 0,
                            ];
                        }
                    }
                }
                return strlen($chunk);
            });

            curl_exec($ch);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return ['error' => "Curl Error: $error"];
            }

            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($statusCode !== 200) {
                return ['error' => "Ollama API returned status $statusCode"];
            }

            return ['success' => true, 'usage' => $usage];
        } catch (\Throwable $e) {
            log_message('error', 'Ollama Stream Failed: ' . $e->getMessage());
            return ['error' => 'Streaming failed: ' . $e->getMessage()];
        }
    }

    public function chat(array $messages, ?string $model = null): array
    {
        $model = $model ?? $this->config->defaultModel;
        $response = $this->generateChat($model, $messages);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']];
        }

        return [
            'success'  => true,
            'response' => $response['result'],
            'model'    => $model,
            'usage'    => $response['usage'] ?? []
        ];
    }

    public function embed(string $input): array
    {
        $url = rtrim($this->config->baseUrl, '/') . '/api/embed';

        $payload = [
            'model'  => $this->config->embeddingModel,
            'input'  => $input
        ];

        try {
            log_message('info', 'Ollama Embed Request: ' . json_encode($payload));

            $response = $this->client->post($url, [
                'body'        => json_encode($payload),
                'headers'     => ['Content-Type' => 'application/json'],
                'http_errors' => false
            ]);

            if ($response->getStatusCode() !== 200) {
                log_message('error', 'Ollama Embed Error: ' . $response->getBody());
                return [];
            }

            $data = json_decode($response->getBody(), true);

            $embedding = [];
            if (isset($data['embeddings']) && is_array($data['embeddings'])) {
                $embedding = $data['embeddings'][0] ?? [];
            } elseif (isset($data['embedding'])) {
                $embedding = $data['embedding'];
            }

            if (empty($embedding)) {
                log_message('error', 'Ollama Embed Empty Response: ' . json_encode($data));
            } else {
                log_message('info', 'Ollama Embed Success. Vector Size: ' . count($embedding));
            }

            return $embedding;
        } catch (\Exception $e) {
            log_message('error', 'Ollama Embed Failed: ' . $e->getMessage());
            return [];
        }
    }
}
