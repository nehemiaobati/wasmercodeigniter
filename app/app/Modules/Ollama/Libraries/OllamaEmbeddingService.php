<?php

declare(strict_types=1);

namespace App\Modules\Ollama\Libraries;

use App\Modules\Ollama\Config\Ollama as OllamaConfig;
use CodeIgniter\HTTP\CURLRequest;
use Config\Services;

/**
 * Handles generating vector embeddings using the Ollama API.
 */
class OllamaEmbeddingService
{
    protected ?OllamaConfig $config;
    protected ?CURLRequest $client;

    public function __construct(?OllamaConfig $config = null, ?CURLRequest $client = null)
    {
        $this->config = $config ?? config(OllamaConfig::class);
        $this->client = $client ?? Services::curlrequest([
            'timeout' => $this->config->timeout,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Generate Vector Embeddings
     *
     * @param string $input
     * @return array ['status' => 'success'|'error', 'data' => array, 'message' => string]
     */
    public function getEmbedding(string $input): array
    {
        if (empty($input)) {
            return ['status' => 'error', 'message' => 'Input text cannot be empty', 'data' => []];
        }

        $url = rtrim($this->config->baseUrl, '/') . '/api/embed';
        $payload = [
            'model' => $this->config->embeddingModel,
            'input' => $input
        ];

        try {
            $response = $this->client->post($url, [
                'body'        => json_encode($payload),
                'headers'     => ['Content-Type' => 'application/json'],
                'http_errors' => false
            ]);

            if ($response->getStatusCode() !== 200) {
                log_message('error', "[Ollama EmbeddingService] API Error: " . $response->getStatusCode() . " - " . $response->getBody());
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
            log_message('error', "[Ollama EmbeddingService] Connection failed: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to connect to Ollama server', 'data' => []];
        }
    }
}
