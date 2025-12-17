<?php

namespace App\Modules\Ollama\Libraries;

use App\Modules\Ollama\Config\Ollama as OllamaConfig;

/**
 * Ollama Payload Service
 *
 * Constructs properly formatted API payloads for Ollama's /api/chat endpoint.
 * Separates payload construction logic from the main service for better modularity.
 *
 * Responsibilities:
 * - Format messages array according to Ollama API specification
 * - Add multimodal content (images) when provided
 * - Build complete request configuration (URL + JSON body)
 *
 * Refactoring Notes:
 * - Uses array_map() for cleaner message transformation (replaced foreach loop)
 * - Constructor property promotion for dependency injection
 *
 * @package App\Modules\Ollama\Libraries
 */
class OllamaPayloadService
{
    /**
     * Constructor with Property Promotion (PHP 8.0+)
     *
     * @param OllamaConfig $config Configuration object for base URL and default model
     */
    public function __construct(
        protected OllamaConfig $config = new OllamaConfig()
    ) {}

    /**
     * Build Complete Payload Configuration for Ollama API
     *
     * Constructs the API request URL and JSON payload body according to Ollama's
     * chat completion specification. Handles both text-only and multimodal (text + images) requests.
     *
     * Refactoring: Message formatting now uses array_map() (line 18-28) instead of a
     * traditional foreach loop for more functional, declarative code.
     *
     * @param string $model Model name (e.g., 'llama3', 'mistral', 'llava')
     * @param array $messages Messages array in standard format [{'role': 'user', 'content': '...', 'images': [...]}]
     * @param bool $stream Whether to enable streaming mode (SSE)
     * @param array $options Additional Ollama parameters (temperature, top_p, etc.)
     * @return array ['url' => string, 'body' => string (JSON)]
     */
    public function getPayloadConfig(string $model, array $messages, bool $stream = false, array $options = []): array
    {
        $url = rtrim($this->config->baseUrl, '/') . '/api/chat';

        // Format messages for Ollama API spec (refactored with array_map for cleaner code)
        // Each message: {"role": "user|system|assistant", "content": "text", "images": ["base64..."]}
        $formattedMessages = array_map(function ($msg) {
            $formattedMsg = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];

            // Add images array if present (for vision models like llava)
            if (!empty($msg['images'])) {
                $formattedMsg['images'] = $msg['images'];
            }

            return $formattedMsg;
        }, $messages);

        // Build complete payload
        $payload = [
            'model' => $model ?: $this->config->defaultModel,
            'messages' => $formattedMessages,
            'stream' => $stream,
        ];

        // Merge in any additional model options (temperature, etc.)
        if (!empty($options)) {
            $payload['options'] = $options;
        }

        return [
            'url' => $url,
            'body' => json_encode($payload)
        ];
    }
}
