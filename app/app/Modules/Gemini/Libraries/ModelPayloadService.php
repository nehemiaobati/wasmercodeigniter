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
 * STANDALONE PATTERN MANDATE:
 * To ensure granular maintenance and prevent unintended regressions, each model configuration
 * must remain separate and independent. ID grouping (mapping multiple model IDs to a single configuration block)
 * is strictly permitted ONLY if the payload structures and parameters are 100% identical.
 *
 * Uses PHP 8.0 match expression with structured configuration for clean,
 * self-contained model definitions.
 *
 * @package App\Modules\Gemini\Libraries
 */
class ModelPayloadService
{
    // --- Helper Methods ---

    /**
     * Extracts plain text from a parts array, ignoring images/files.
     * Essential for models (Imagen/legacy Veo) that crash if sent multimodal input arrays.
     *
     * @param array $parts
     * @return string
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
     * Builds the 'instances' data for Veo models.
     * Handles prioritization: Video (Extension) > Image (Animation).
     * Note: referenceImages not supported via REST API predictLongRunning endpoint.
     *
     * @param array $parts
     * @return array
     */
    private function _buildVeoData(array $parts): array
    {
        $primaryVideo = null;
        $images = [];
        $text = '';

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'] . ' ';
            }

            if (isset($part['inlineData'])) {
                $mimeType = $part['inlineData']['mimeType'] ?? '';
                $data = $part['inlineData']['data'];

                if (str_starts_with($mimeType, 'video/')) {
                    if (!$primaryVideo) {
                        $primaryVideo = ['bytesBase64Encoded' => $data, 'mimeType' => $mimeType];
                    }
                } elseif (str_starts_with($mimeType, 'image/')) {
                    $images[] = ['bytesBase64Encoded' => $data, 'mimeType' => $mimeType];
                }
            }
        }

        $result = [
            'instance' => ['prompt' => trim($text)]
        ];

        // Assign primary media to instance
        if ($primaryVideo) {
            $result['instance']['video'] = $primaryVideo;
        } elseif (!empty($images)) {
            // First image is the animate target
            $result['instance']['image'] = array_shift($images);
        }

        return $result;
    }

    /**
     * Standardizes the API endpoint construction.
     *
     * @param string $modelId
     * @param string $method
     * @param string $apiKey
     * @return string
     */
    private function _buildEndpoint(string $modelId, string $method, string $apiKey): string
    {
        return "https://generativelanguage.googleapis.com/v1beta/models/{$modelId}:{$method}?key=" . urlencode($apiKey);
    }

    // --- Public API ---

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
        // Standalone pattern: each model has its own entry for maximum maintenance isolation.
        $config = match ($modelId) {
            // --- Advanced Thinking Models (Pro) ---
            'gemini-3-pro-preview',
            'gemini-3-flash-preview' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "thinkingConfig" => ["thinkingLevel" => "HIGH", "includeThoughts" => true],
                        "temperature" => 1,
                        "topP" => 0.95,
                        "maxOutputTokens" => 64000,
                    ],
                    "tools" => [["googleSearch" => new stdClass()]],
                ]
            ],
            'gemini-2.5-pro' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "thinkingConfig" => ["thinkingBudget" => 32768, "includeThoughts" => true],
                        "temperature" => 1,
                        "topP" => 0.95,
                        "maxOutputTokens" => 64000,
                    ],
                    "tools" => [["googleSearch" => new stdClass()]],
                ]
            ],

            // --- Standard Flash Models ---
            'gemini-flash-latest',
            'gemini-2.5-flash',
            'gemini-flash-lite-latest',
            'gemini-2.5-flash-lite' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "thinkingConfig" => ["thinkingBudget" => 2048, "includeThoughts" => true],
                        "temperature" => 1,
                        "topP" => 0.95,
                        "maxOutputTokens" => 64000,
                    ],
                    "tools" => [["googleSearch" => new stdClass()]],
                ]
            ],

            // --- Legacy Flash Models (2.0) ---
            'gemini-2.0-flash' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "temperature" => 1,
                        "topP" => 0.95,
                        "maxOutputTokens" => 64000,
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
                        "maxOutputTokens" => 64000,
                    ],
                ]
            ],

            // --- Multimodal Generation (Image + Text Output) ---
            'gemini-3-pro-image-preview' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "responseModalities" => ["IMAGE", "TEXT"],
                        "imageConfig" => ["image_size" => "1K"],
                        "temperature" => 1,
                        "topP" => 0.95,
                        "maxOutputTokens" => 64000,
                    ],
                    "tools" => [["googleSearch" => new stdClass()]],
                ]
            ],
            'gemini-2.5-flash-image',
            'gemini-2.5-flash-image-preview' => [
                'method' => $isStream ? 'streamGenerateContent' : 'generateContent',
                'payload' => [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "responseModalities" => ["IMAGE", "TEXT"],
                        "temperature" => 1,
                        "topP" => 0.95,
                        "maxOutputTokens" => 64000,
                    ],
                ]
            ],

            // --- Imagen 4.0 Models ---
            'imagen-4.0-generate-preview-06-06',
            'imagen-4.0-ultra-generate-preview-06-06',
            'imagen-4.0-ultra-generate-001',
            'imagen-4.0-fast-generate-001',
            'imagen-4.0-generate-001' => [
                'method' => 'predict',
                'payload' => [
                    "instances" => [["prompt" => $this->_extractTextPrompt($parts)]],
                    "parameters" => [
                        "sampleCount" => 1,
                        "personGeneration" => "allow_all",
                        "aspectRatio" => "1:1",
                        "imageSize" => "1K",
                        "outputMimeType" => "image/jpeg",
                    ]
                ]
            ],

            // --- Veo 3.x Models (REST API does not support referenceImages) ---
            'veo-3.1-generate-preview',
            'veo-3.1-fast-generate-preview',
            'veo-3.0-generate-001',
            'veo-3.0-fast-generate-001' => [
                'method' => 'predictLongRunning',
                'payload' => [
                    'instances' => [$this->_buildVeoData($parts)['instance']],
                    'parameters' => [
                        'aspectRatio' => '16:9',
                        'sampleCount' => 1
                    ]
                ]
            ],
            'veo-2.0-generate-001' => [
                'method' => 'predictLongRunning',
                'payload' => [
                    'instances' => [$this->_buildVeoData($parts)['instance']],
                    'parameters' => [
                        'sampleCount' => 1,
                        'personGeneration' => 'allow_all'
                    ]
                ]
            ],

            default => null
        };

        if ($config === null) return null;

        return [
            'url' => $this->_buildEndpoint($modelId, $config['method'], $apiKey),
            'body' => json_encode($config['payload'])
        ];
    }
}
