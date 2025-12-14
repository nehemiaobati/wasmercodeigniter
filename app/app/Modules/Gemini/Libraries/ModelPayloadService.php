<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use stdClass;

/**
 * Service responsible for generating model-specific configurations and payloads.
 * Implements the "Standalone" pattern for infinite model scalability.
 */
class ModelPayloadService
{
    /**
     * Returns the specific API Endpoint URL and JSON Request Body for a given model.
     *
     * @param string $modelId The specific model ID.
     * @param string $apiKey The API Key.
     * @param array $parts The content parts (user input/images).
     * @param bool $isStream Whether to use the streaming endpoint.
     * @return array|null ['url' => string, 'body' => string] or null if model not supported.
     */
    public function getPayloadConfig(string $modelId, string $apiKey, array $parts, bool $isStream = false): ?array
    {
        $payload = null;
        // Default method is generateContent, overridden in specific cases (Imagen/Veo)
        $apiMethod = $isStream ? 'streamGenerateContent' : 'generateContent';

        switch ($modelId) {
            // ----------------------------------------------------------------
            // 1. ADVANCED THINKING MODELS (Pro)
            // ----------------------------------------------------------------
            case 'gemini-3-pro-preview':
                $payload = [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => ["thinkingConfig" => ["thinkingLevel" => "HIGH"]],
                    "tools" => [["googleSearch" => new stdClass()]],
                ];
                break;

            case 'gemini-2.5-pro':
                $payload = [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => ["thinkingConfig" => ["thinkingBudget" => 32768]],
                    "tools" => [["googleSearch" => new stdClass()]],
                ];
                break;

            // ----------------------------------------------------------------
            // 2. STANDARD FLASH MODELS (Thinking Disabled/Low)
            // ----------------------------------------------------------------
            case 'gemini-flash-latest':
            case 'gemini-2.5-flash':
                $payload = [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => ["thinkingConfig" => ["thinkingBudget" => -1]],
                    "tools" => [["googleSearch" => new stdClass()]],
                ];
                break;

            case 'gemini-flash-lite-latest':
            case 'gemini-2.5-flash-lite':
                $payload = [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => ["thinkingConfig" => ["thinkingBudget" => 0]],
                    "tools" => [["googleSearch" => new stdClass()]],
                ];
                break;

            // ----------------------------------------------------------------
            // 3. LEGACY FLASH MODELS (2.0)
            // ----------------------------------------------------------------
            case 'gemini-2.0-flash':
                $payload = [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => new stdClass(), // Force {}
                    "tools" => [["googleSearch" => new stdClass()]],
                ];
                break;

            case 'gemini-2.0-flash-lite':
                $payload = [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => new stdClass(),
                    // No tools for 2.0 Flash Lite
                ];
                break;

            // ----------------------------------------------------------------
            // 4. MULTIMODAL GENERATION (Image + Text Output)
            // ----------------------------------------------------------------
            case 'gemini-3-pro-image-preview':
                $payload = [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "responseModalities" => ["IMAGE", "TEXT"],
                        "imageConfig" => ["image_size" => "1K"],
                    ],
                    "tools" => [["googleSearch" => new stdClass()]],
                ];
                break;

            case 'gemini-2.5-flash-image':
            case 'gemini-2.5-flash-image-preview':
                $payload = [
                    "contents" => [["role" => "user", "parts" => $parts]],
                    "generationConfig" => [
                        "responseModalities" => ["IMAGE", "TEXT"],
                    ],
                ];
                break;

            // ----------------------------------------------------------------
            // 5. IMAGEN 4.0 (Text-to-Image / Predict Endpoint)
            // ----------------------------------------------------------------
            case 'imagen-4.0-generate-preview-06-06':
            case 'imagen-4.0-ultra-generate-preview-06-06':
            case 'imagen-4.0-ultra-generate-001':
            case 'imagen-4.0-fast-generate-001':
            case 'imagen-4.0-generate-001':
                $apiMethod = 'predict';
                $payload = [
                    "instances" => [["prompt" => $this->_extractTextPrompt($parts)]],
                    "parameters" => [
                        "outputMimeType" => "image/jpeg",
                        "sampleCount" => 1,
                        "personGeneration" => "ALLOW_ALL",
                        "aspectRatio" => "1:1",
                        "imageSize" => "1K", // Defaulting to 1K for consistency
                    ]
                ];
                break;

            // ----------------------------------------------------------------
            // 6. VEO 2.0 (Text-to-Video / Async Endpoint)
            // ----------------------------------------------------------------
            case 'veo-2.0-generate-001':
                $apiMethod = 'predictLongRunning';
                $payload = [
                    "instances" => [["prompt" => $this->_extractTextPrompt($parts)]],
                    "parameters" => [
                        "aspectRatio" => "16:9",
                        "sampleCount" => 1,
                        "durationSeconds" => 8,
                        "personGeneration" => "ALLOW_ALL",
                    ]
                ];
                break;

            default:
                // Strict: Return null for unknown models to ensure explicit configuration
                return null;
        }

        return [
            'url' => $this->_buildEndpoint($modelId, $apiMethod, $apiKey),
            'body' => json_encode($payload)
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
