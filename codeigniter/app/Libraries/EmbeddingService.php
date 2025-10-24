<?php declare(strict_types=1);

namespace App\Libraries;

use Config\AGI;

/**
 * Handles the generation of vector embeddings via the Gemini API.
 */
class EmbeddingService
{
    private string $apiKey;
    private string $modelId;
    private string $apiUrl;
    private bool $isEnabled;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $config = config(AGI::class);
        $this->modelId = $config->embeddingModel;
        $this->isEnabled = $config->enableEmbeddings;
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->modelId}:embedContent?key={$this->apiKey}";
    }

    /**
     * Converts a string of text into a vector embedding.
     *
     * @param string $text The text to embed.
     * @return array|null The vector as an array of floats, or null on error or if disabled.
     */
    public function getEmbedding(string $text): ?array
    {
        if (!$this->isEnabled || empty($this->apiKey)) {
            return null;
        }

        try {
            $client = \Config\Services::curlrequest();
            $response = $client->post($this->apiUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'model'   => "models/{$this->modelId}",
                    'content' => ['parts' => [['text' => $text]]]
                ]
            ]);

            $decodedResponse = json_decode($response->getBody(), true);
            $embedding = $decodedResponse['embedding']['values'] ?? null;

            if ($embedding === null) {
                log_message('error', "Failed to find embedding in API response: " . $response->getBody());
                return null;
            }
            return $embedding;
        } catch (\Exception $e) {
            log_message('error', "cURL Error getting embedding: " . $e->getMessage());
            return null;
        }
    }
}
