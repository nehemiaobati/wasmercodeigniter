<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use stdClass;

/**
 * Constructs provider-specific request payloads for the Gemini API.
 *
 * Implements the "Standalone" pattern to ensure model scalability.
 * Each model configuration remains independent to prevent regressions during maintenance.
 */
class ModelPayloadService
{
    // --- Helper Methods ---

    /**
     * Filters input parts to extract concatenated text content.
     *
     * @param array $parts Multimodal input structure.
     * @return string Concatenated prompt text.
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
     * Synthesizes 'instances' payload for video generation providers.
     *
     * @param array $parts Multimodal input structures.
     * @return array Formatted Google Vertex/GenAI instance packet.
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
     * @param string $modelId Provider model identifier.
     * @param string $method Target API RPC action.
     * @param string $apiKey Authentication credential.
     * @return string Fully qualified request URI.
     */
    private function _buildEndpoint(string $modelId, string $method, string $apiKey): string
    {
        return "https://generativelanguage.googleapis.com/v1beta/models/{$modelId}:{$method}?key=" . urlencode($apiKey);
    }

    // --- Public API ---

    /**
     * Generates the API endpoint and encoded body for a specific model request.
     *
     * Uses a structural match expression to return:
     * - 'method': Target API RPC method.
     * - 'payload': Model-specific parameter hierarchy.
     *
     * @param string $modelId Unique provider identifier.
     * @param string $apiKey Resource access key.
     * @param array $parts Input multimodal parts.
     * @param bool $isStream Toggle for SSE endpoint selection.
     * @return array|null structured payload packet.
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
