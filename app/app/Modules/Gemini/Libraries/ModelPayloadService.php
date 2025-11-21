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
     * @return array ['url' => string, 'body' => string]
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
