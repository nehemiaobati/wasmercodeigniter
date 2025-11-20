<?php declare(strict_types=1);

namespace App\Modules\Ollama\Libraries;

use App\Modules\Ollama\Config\Ollama;
use CodeIgniter\HTTP\CURLRequest;
use Config\Services;

class OllamaService
{
    private Ollama $config;
    private CURLRequest $client;

    public function __construct()
    {
        $this->config = config(Ollama::class);

        // Ensure we have a valid base URL, defaulting if empty
        if (empty($this->config->baseUrl)) {
            $this->config->baseUrl = 'http://127.0.0.1:11434';
        }
        

        // Initialize Client without 'base_uri' to prevent merging issues
        $this->client = Services::curlrequest([
            'timeout'  => $this->config->timeout,
            'headers'  => ['Content-Type' => 'application/json'],
        ]);
    }

    /**
     * Checks if the Ollama server is reachable.
     */
    public function isOnline(): bool
    {
        try {
            // EXPLICIT URL: Guaranteed to have a host part
            $url = $this->config->baseUrl ; 
            
            $response = $this->client->get($url);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            log_message('error', '[Ollama Check Failed] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generates a chat response.
     */
    public function chat(array $messages): array
    {
        try {
            // EXPLICIT URL
            $url = $this->config->baseUrl . '/api/chat';
            
            $payload = [
                'model'    => $this->config->chatModel,
                'messages' => $messages,
                'stream'   => false,
               // 'options'  => ['temperature' => 1],
            ]
            ;

            $response = $this->client->post($url, ['json' => $payload]);
            
            if ($response->getStatusCode() !== 200) {
                return ['success' => false, 'error' => 'Ollama API status: ' . $response->getStatusCode()];
            }

            $data = json_decode($response->getBody(), true);
            
            return [
                'success' => true,
                'response' => $data['message']['content'] ?? '',
                'model'    => $data['model'] ?? 'unknown'
            ];

        } catch (\Exception $e) {
            log_message('error', '[Ollama Chat Error] ' . $e->getMessage());
            return ['success' => false, 'error' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Generates vector embeddings for a text string.
     */
    public function embed(string $text): ?array
    {
        try {
            // EXPLICIT URL
            $url = $this->config->baseUrl . '/api/embeddings';

            $response = $this->client->post($url, [
                'json' => [
                    'model'  => $this->config->embeddingModel,
                    'prompt' => $text
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['embedding'] ?? null;

        } catch (\Exception $e) {
            log_message('error', '[Ollama Embed Error] ' . $e->getMessage());
            return null;
        }
    }
}