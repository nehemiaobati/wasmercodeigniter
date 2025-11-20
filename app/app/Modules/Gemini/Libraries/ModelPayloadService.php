<?php declare(strict_types=1);

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
     * @param string $modelId The specific model ID (e.g., gemini-3-pro-preview).
     * @param string $apiKey The API Key.
     * @param array $parts The content parts (user input/images).
     * @return array ['url' => string, 'body' => string]
     */
    public function getPayloadConfig(string $modelId, string $apiKey, array $parts): ? array
    {
        // Base URL construction - allows for switching between stream and standard if needed in future
        // Keeping 'generateContent' for compatibility with existing GeminiService parsing logic
        // If streaming is strictly required by the model, this string can be changed to 'streamGenerateContent'
        $apiMethod = 'generateContent'; 
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelId}:{$apiMethod}?key=" . urlencode($apiKey);

        $payload = [];

        switch ($modelId) {
            case 'gemini-3-pro-preview':
                // Configuration: Thinking Level HIGH, Google Search Tools
                $payload = [
                    "contents" => [
                        [
                            "role" => "user",
                            "parts" => $parts
                        ]
                    ],
                    "generationConfig" => [
                        "thinkingConfig" => [
                            "thinkingLevel" => "HIGH",
                        ],
                    ],
                    "tools" => [
                        [
                            "googleSearch" => new \stdClass() // Empty object for JSON
                        ]
                    ],
                ];
                break;
            case 'gemini-2.5-pro':
                $payload = [
                    "contents" => [
                        [
                            "role" => "user",
                            "parts" => $parts
                        ]
                    ],
                    "generationConfig" => [
                        "thinkingConfig" => [
                            "thinkingBudget" => 32768,
                        ],
                    ],
                    "tools" => [
                        [
                            "googleSearch" => new \stdClass()
                        ]
                    ],
                ];
                break;
            case 'gemini-flash-latest':
            case 'gemini-2.5-flash':
                $payload = [
                    "contents" => [
                        [
                            "role" => "user",
                            "parts" => $parts
                        ]
                    ],
                    "generationConfig" => [
                        "thinkingConfig" => [
                            "thinkingBudget" => -1,
                        ],
                    ],
                    "tools" => [
                        [
                            "googleSearch" => new \stdClass()
                        ]
                    ],
                ];
                break;
            case 'gemini-flash-lite-latest':
            case 'gemini-2.5-flash-lite':
                // Configuration: Thinking Budget specific, Google Search Tools
                $payload = [
                    "contents" => [
                        [
                            "role" => "user",
                            "parts" => $parts
                        ]
                    ],
                    "generationConfig" => [
                        "thinkingConfig" => [
                            "thinkingBudget" => 0,
                        ],
                    ],
                    "tools" => [
                        [
                            "googleSearch" => new \stdClass()
                        ]
                    ],
                ];
                break;

            case 'gemini-2.0-flash':
                $payload = [
                    "contents" => [
                        [
                            "role" => "user",
                            "parts" => $parts
                        ]
                    ],
                    "generationConfig" => [
                        // Flash models typically don't use thinkingConfig, standard parameters apply
                    ],
                    "tools" => [
                        [
                            "googleSearch" => new \stdClass()
                        ]
                    ],
                ];
                break;
            case 'gemini-2.0-flash-lite':
                // Configuration: Standard Flash configuration, Google Search Tools
                $payload = [
                    "contents" => [
                        [
                            "role" => "user",
                            "parts" => $parts
                        ]
                    ],
                    "generationConfig" => [
                        // Flash models typically don't use thinkingConfig, standard parameters apply
                    ],
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
            'url'  => $url,
            'body' => json_encode($payload)
        ];
    }
}
