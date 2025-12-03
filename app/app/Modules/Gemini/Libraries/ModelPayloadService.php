<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

/**
 * Service responsible for generating model-specific configurations and payloads.
 * Decouples configuration complexity from the execution service.
 */
class ModelPayloadService
{
    /**
     * Returns the specific API Endpoint URL and JSON Request Body for a given model.
     *
     * @param string $modelId The specific model ID.
     * @param string $apiKey The API Key.
     * @param array $parts The content parts (user input/images).
     * @return array|null ['url' => string, 'body' => string] or null if model not supported.
     */
    public function getPayloadConfig(string $modelId, string $apiKey, array $parts): ?array
    {
        // Standard generation endpoint. 
        // Note: Your bash scripts used 'streamGenerateContent', but for standard PHP 
        // request/response cycles without stream handling, 'generateContent' is usually correct.
        $apiMethod = 'generateContent';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelId}:{$apiMethod}?key=" . urlencode($apiKey);

        $payload = [];

        switch ($modelId) {
            case 'gemini-3-pro-preview':
                $payload = [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "thinkingConfig" => ["thinkingLevel" => "HIGH"],
                    ],
                    "tools" => [
                        ["googleSearch" => new \stdClass()]
                    ],
                ];
                break;

            case 'gemini-2.5-pro':
                $payload = [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "thinkingConfig" => ["thinkingBudget" => 32768],
                    ],
                    "tools" => [
                        ["googleSearch" => new \stdClass()]
                    ],
                ];
                break;

            case 'gemini-flash-latest':
            case 'gemini-2.5-flash':
                $payload = [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "thinkingConfig" => ["thinkingBudget" => -1],
                    ],
                    "tools" => [
                        ["googleSearch" => new \stdClass()]
                    ],
                ];
                break;

            case 'gemini-flash-lite-latest':
            case 'gemini-2.5-flash-lite':
                $payload = [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "thinkingConfig" => ["thinkingBudget" => 0],
                    ],
                    "tools" => [
                        ["googleSearch" => new \stdClass()]
                    ],
                ];
                break;

            // -------------------------------------------------------
            // FIX APPLIED HERE
            // -------------------------------------------------------
            case 'gemini-2.0-flash':
                // Matches Bash Script: Has googleSearch tools, Empty Object generationConfig
                $payload = [
                    "contents" => [
                        [
                            "role" => "user",
                            "parts" => $parts
                        ]
                    ],
                    // Use stdClass to force JSON "{}" instead of "[]"
                    "generationConfig" => new \stdClass(),
                    "tools" => [
                        [
                            "googleSearch" => new \stdClass()
                        ]
                    ],
                ];
                break;

            case 'gemini-2.0-flash-lite':
                // Matches Bash Script: NO tools, Empty Object generationConfig
                $payload = [
                    "contents" => [
                        [
                            "role" => "user",
                            "parts" => $parts
                        ]
                    ],
                    // Use stdClass to force JSON "{}" instead of "[]"
                    "generationConfig" => new \stdClass(),
                ];
                break;

            // -------------------------------------------------------
            // IMAGEN 4.0 (Standard/Ultra/Fast)
            // -------------------------------------------------------
            // -------------------------------------------------------
            // GEMINI IMAGE GENERATION (Multimodal)
            // -------------------------------------------------------
            case 'gemini-3-pro-image-preview':
                $payload = [
                    "contents" => [
                        ["role" => "user", "parts" => $parts]
                    ],
                    "generationConfig" => [
                        "responseModalities" => ["IMAGE", "TEXT"],
                        "imageConfig" => [
                            "image_size" => "1K"
                        ],
                    ],
                    "tools" => [
                        ["googleSearch" => new \stdClass()]
                    ],
                ];
                break;

            case 'gemini-2.5-flash-image':
            case 'gemini-2.5-flash-image-preview':
                $payload = [
                    "contents" => [
                        ["role" => "user", "parts" => $parts]
                    ],
                    "generationConfig" => [
                        "responseModalities" => ["IMAGE", "TEXT"],
                    ],
                ];
                break;

            // -------------------------------------------------------
            // IMAGEN 4.0 (Standard/Ultra)
            // -------------------------------------------------------
            // -------------------------------------------------------
            // IMAGEN 4.0 (Standard/Ultra/Fast)
            // -------------------------------------------------------
            case 'imagen-4.0-generate-preview-06-06':
            case 'imagen-4.0-ultra-generate-preview-06-06':
            case 'imagen-4.0-ultra-generate-001':
            case 'imagen-4.0-fast-generate-001':
            case 'imagen-4.0-generate-001':
                // Extract prompt (Text ONLY)
                // Worst-case handling: If user sends image+text, we MUST strip the image 
                // because Imagen 'predict' endpoint will 400 Error on unknown fields or complex structures.
                $promptText = '';
                foreach ($parts as $part) {
                    if (isset($part['text'])) $promptText .= $part['text'] . ' ';
                }
                $promptText = trim($promptText);

                $payload = [
                    "instances" => [
                        ["prompt" => $promptText]
                    ],
                    "parameters" => [
                        "outputMimeType" => "image/jpeg",
                        "sampleCount" => 1,
                        "personGeneration" => "ALLOW_ALL",
                        "aspectRatio" => "1:1",
                        "imageSize" => "1K",
                    ]
                ];
                $apiMethod = 'predict';
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelId}:{$apiMethod}?key=" . urlencode($apiKey);
                break;

            // -------------------------------------------------------
            // VEO 2.0 (Video Generation)
            // -------------------------------------------------------
            case 'veo-2.0-generate-001':
                // Extract prompt (Text ONLY)
                // Veo currently only supports text-to-video via this specific endpoint structure.
                $promptText = '';
                foreach ($parts as $part) {
                    if (isset($part['text'])) $promptText .= $part['text'] . ' ';
                }
                $promptText = trim($promptText);

                $payload = [
                    "instances" => [
                        ["prompt" => $promptText]
                    ],
                    "parameters" => [
                        "aspectRatio" => "16:9",
                        "sampleCount" => 1,
                        "durationSeconds" => 8,
                        "personGeneration" => "ALLOW_ALL",
                    ]
                ];
                $apiMethod = 'predictLongRunning';
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelId}:{$apiMethod}?key=" . urlencode($apiKey);
                break;

            default:
                // RETURN NULL if the model is not explicitly configured.
                // This prevents 'generic' payloads from failing on specialized models.
                return null;
        }

        // This return is only reached if a specific model case is matched.
        // If default is hit, the function exits with null.
        return [
            'url' => $url,
            'body' => json_encode($payload)
        ];
    }
}
