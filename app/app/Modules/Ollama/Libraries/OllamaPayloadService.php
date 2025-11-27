<?php

namespace App\Modules\Ollama\Libraries;

use App\Modules\Ollama\Config\Ollama as OllamaConfig;

class OllamaPayloadService
{
    protected OllamaConfig $config;

    public function __construct()
    {
        $this->config = new OllamaConfig();
    }

    /**
     * Constructs the payload configuration for the Ollama API.
     *
     * @param string $model The model to use (e.g., 'llama3').
     * @param array $messages The conversation history/messages.
     * @param bool $stream Whether to stream the response.
     * @param array $options Additional model parameters (temperature, etc.).
     * @return array ['url' => string, 'body' => string]
     */
    public function getPayloadConfig(string $model, array $messages, bool $stream = false, array $options = []): array
    {
        $url = rtrim($this->config->baseUrl, '/') . '/api/chat';

        // Format messages for Ollama
        // Ollama expects: [{ "role": "user", "content": "message", "images": ["base64..."] }]
        $formattedMessages = [];
        foreach ($messages as $msg) {
            $formattedMsg = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];

            if (!empty($msg['images'])) {
                $formattedMsg['images'] = $msg['images'];
            }

            $formattedMessages[] = $formattedMsg;
        }

        $payload = [
            'model' => $model ?: $this->config->defaultModel,
            'messages' => $formattedMessages,
            'stream' => $stream,
        ];

        if (!empty($options)) {
            $payload['options'] = $options;
        }

        return [
            'url' => $url,
            'body' => json_encode($payload)
        ];
    }
}
