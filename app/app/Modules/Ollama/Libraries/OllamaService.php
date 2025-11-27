<?php

namespace App\Modules\Ollama\Libraries;

use App\Modules\Ollama\Config\Ollama as OllamaConfig;
use CodeIgniter\HTTP\CURLRequest;
use Config\Services;

class OllamaService
{
    protected OllamaConfig $config;
    protected OllamaPayloadService $payloadService;
    protected CURLRequest $client;

    public function __construct()
    {
        $this->config = new OllamaConfig();
        $this->payloadService = new OllamaPayloadService();
        $this->client = Services::curlrequest([
            'timeout' => $this->config->timeout,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Checks if the Ollama instance is reachable.
     *
     * @return bool
     */
    public function checkConnection(): bool
    {
        try {
            $url = rtrim($this->config->baseUrl, '/') . '/'; // Root endpoint usually returns status
            $response = $this->client->get($url);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            log_message('error', 'Ollama Connection Check Failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches available models from the Ollama instance.
     *
     * @return array List of model names.
     */
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
            return []; // Return empty on failure
        }
    }

    /**
     * Generates a chat response from Ollama.
     *
     * @param string $model The model to use.
     * @param array $messages The conversation history.
     * @return array ['result' => string, 'usage' => array, 'error' => string|null]
     */
    public function generateChat(string $model, array $messages): array
    {
        // 1. Prepare Payload
        $config = $this->payloadService->getPayloadConfig($model, $messages, false); // stream=false for now

        try {
            // 2. Send Request
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

            // 3. Parse Response
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
    /**
     * Wrapper for chat generation to match MemoryService expectation.
     *
     * @param array $messages
     * @param string|null $model
     * @return array
     */
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

    /**
     * Generates embeddings for the given text.
     *
     * @param string $input
     * @return array
     */
    public function embed(string $input): array
    {
        // Use the new /api/embed endpoint (Ollama 0.1.26+)
        $url = rtrim($this->config->baseUrl, '/') . '/api/embed';

        $payload = [
            'model'  => $this->config->embeddingModel,
            'input'  => $input // 'input' instead of 'prompt' for /api/embed
        ];

        try {
            log_message('info', 'Ollama Embed Request: ' . json_encode($payload));

            $response = $this->client->post($url, [
                'body'        => json_encode($payload),
                'headers'     => ['Content-Type' => 'application/json'],
                'http_errors' => false // Prevent exception on 4xx/5xx to capture body
            ]);

            if ($response->getStatusCode() !== 200) {
                log_message('error', 'Ollama Embed Error: ' . $response->getBody());
                return [];
            }

            $data = json_decode($response->getBody(), true);

            // /api/embed returns 'embeddings' (array of arrays)
            $embedding = [];
            if (isset($data['embeddings']) && is_array($data['embeddings'])) {
                $embedding = $data['embeddings'][0] ?? [];
            } elseif (isset($data['embedding'])) {
                // Fallback for older versions or different response shapes
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
