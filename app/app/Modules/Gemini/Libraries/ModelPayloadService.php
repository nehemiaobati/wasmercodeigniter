<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use stdClass;

/**
 * Model Payload Service
 *
 * Generates model-specific configurations and payloads for the Gemini API.
 * Implements the "Standalone" pattern for infinite model scalability.
 *
 * Uses PHP 8.0 match expression with structured configuration for clean,
 * self-contained model definitions.
 *
 * @package App\Modules\Gemini\Libraries
 */
class ModelPayloadService
{
    /**
     * Returns the specific API Endpoint URL and JSON Request Body for a given model.
     *
     * Uses PHP 8.0 match expression that returns structured configuration:
     * - 'method': API method name (generateContent, predict, predictLongRunning, etc.)
     * - 'payload': Model-specific request payload
     *
     * This approach eliminates post-match correction logic by having each model
     * define its own complete configuration.
     *
     * @param string $modelId The specific model ID.
     * @param string $apiKey The API Key.
     * @param array $parts The content parts (user input/images).
     * @param bool $isStream Whether to use the streaming endpoint.
     * @return array|null ['url' => string, 'body' => string] or null if model not supported.
     */
    public function getPayloadConfig(string $modelId, string $apiKey, array $parts, bool $isStream = false): ?array
    {
        // Match expression returns structured config with method + payload
        $config = match ($modelId) {
            // Advanced Thinking Models (Pro) - Standard API
            'gemini-3-pro-preview' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "thinkingConfig" => ["thinkingLevel" => "HIGH"],
                        "temperature" => 1,
                        "topP" => 0.95,
                    ],
                    "tools" => [["googleSearch" => new stdClass()]],
                ]
            ],
            'gemini-2.5-pro' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "thinkingConfig" => ["thinkingBudget" => 32768],
                        "temperature" => 1,
                        "topP" => 0.95,
                    ],
                    "tools" => [["googleSearch" => new stdClass()]],
                ]
            ],

            // Standard Flash Models - Standard API
            'gemini-flash-latest', 'gemini-2.5-flash' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "thinkingConfig" => ["thinkingBudget" => -1],
                        "temperature" => 1,
                        "topP" => 0.95,
                    ],
                    "tools" => [["googleSearch" => new stdClass()]],
                ]
            ],
            'gemini-flash-lite-latest', 'gemini-2.5-flash-lite' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "thinkingConfig" => ["thinkingBudget" => 0],
                        "temperature" => 1,
                        "topP" => 0.95,
                    ],
                    "tools" => [["googleSearch" => new stdClass()]],
                ]
            ],

            // Legacy Flash Models (2.0) - Standard API
            'gemini-2.0-flash' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "temperature" => 1,
                        "topP" => 0.95,
                    ],
                    "tools" => [["googleSearch" => new stdClass()]],
                ]
            ],
            'gemini-2.0-flash-lite' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "temperature" => 1,
                        "topP" => 0.95,
                    ],
                ]
            ],

            // Multimodal Generation (Image + Text Output) - Standard API
            'gemini-3-pro-image-preview' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "responseModalities" => ["IMAGE", "TEXT"],
                        "imageConfig" => ["image_size" => "1K"],
                        "temperature" => 1,
                        "topP" => 0.95,
                    ],
                    "tools" => [["googleSearch" => new stdClass()]],
                ]
            ],
            'gemini-2.5-flash-image', 'gemini-2.5-flash-image-preview' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "responseModalities" => ["IMAGE", "TEXT"],
                        "temperature" => 1,
                        "topP" => 0.95,
                    ],
                ]
            ],

            // Imagen 4.0 (Text-to-Image) - Uses predict API
            'imagen-4.0-generate-preview-06-06',
            'imagen-4.0-ultra-generate-preview-06-06',
            'imagen-4.0-ultra-generate-001',
            'imagen-4.0-fast-generate-001',
            'imagen-4.0-generate-001' => [
                'method' => 'predict',
                'payload' => [
                    "instances" => [["prompt" => $this->_extractTextPrompt($parts)]],
                    "parameters" => [
                        "outputMimeType" => "image/jpeg",
                        "sampleCount" => 1,
                        "personGeneration" => "ALLOW_ALL",
                        "aspectRatio" => "1:1",
                        "imageSize" => "1K",
                    ]
                ]
            ],

            // Veo 2.0 (Text-to-Video) - Uses async predictLongRunning API
            'veo-2.0-generate-001' => [
                'method' => 'predictLongRunning',
                'payload' => [
                    "instances" => [["prompt" => $this->_extractTextPrompt($parts)]],
                    "parameters" => [
                        "aspectRatio" => "16:9",
                        "sampleCount" => 1,
                        "durationSeconds" => 8,
                        "personGeneration" => "ALLOW_ALL",
                    ]
                ]
            ],

            // Unknown model
            default => null
        };

        // Return null if model not supported
        if ($config === null) {
            return null;
        }

        // Build and return final URL + payload
        return [
            'url' => $this->_buildEndpoint($modelId, $config['method'], $apiKey),
            'body' => json_encode($config['payload'])
        ];
    }

    /**
     * Extracts plain text from a parts array, ignoring images/files.
     * Essential for models (Imagen/Veo) that crash if sent multimodal input arrays.
     */
    private function _extractTextPrompt(array $parts): string
    {
        $text = '';
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'] . ' ';
            }
        }
        return trim($text);
    }

    /**
     * Standardizes the API endpoint construction.
     */
    private function _buildEndpoint(string $modelId, string $method, string $apiKey): string
    {
        return "https://generativelanguage.googleapis.com/v1beta/models/{$modelId}:{$method}?key=" . urlencode($apiKey);
    }
}
