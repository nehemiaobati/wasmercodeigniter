<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use App\Modules\Gemini\Libraries\ModelPayloadService;

/**
 * Service layer for interacting with the Google Gemini API.
 */
class GeminiService
{
    /**
     * The API key for authenticating with the Gemini API.
     * @var string|null
     */
    protected $apiKey;

    /**
     * Reference to the payload configuration service
     * @var ModelPayloadService
     */
    protected $payloadService;

    /**
     * An ordered list of Gemini model IDs to try, from most preferred to least preferred.
     * Updated to reflect new complexity requirements.
     * @var array<string>
     */
    protected array $modelPriorities = [
        //"gemini-3-pro-preview", // Multimodal, Thinking Level High
        //"gemini-2.5-pro",       // Multimodal, Thinking Budget
        "gemini-flash-latest",
        "gemini-flash-lite-latest", // Standard Fast
        "gemini-2.5-flash",
        "gemini-2.5-flash-lite", // Standard Fast
        "gemini-2.0-flash",      // Fallbacks
        "gemini-2.0-flash-lite", // Fallbacks
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY') ?? getenv('GEMINI_API_KEY');
        // Initialize the specific configuration service
        $this->payloadService = new ModelPayloadService();
        // Alternatively use service('modelPayloadService') if registered in Services.php
    }

    /**
     * Sends a request to the Gemini API for text generation.
     *
     * @param array $parts An array of content parts.
     * @return array An associative array with 'result' (string) and 'usage' (array) or 'error' (string).
     */
    public function generateContent(array $parts): array
    {
        // This method remains unchanged and is fully functional.
        if (!$this->apiKey) {
            return ['error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $apiKey = trim($this->apiKey);
        $lastError = ['error' => 'An unexpected error occurred after multiple retries.'];

        if (empty($this->modelPriorities)) {
            return ['error' => 'No Gemini models configured in modelPriorities.'];
        }

        foreach ($this->modelPriorities as $model) {
            $currentModel = $model;

            // --- CHANGED: logic delegates to PayloadService ---
            // We get the decoupled full request configuration here
            // 1. Get the config
            $config = $this->payloadService->getPayloadConfig($currentModel, $apiKey, $parts);

            // 2. SAFETY CHECK: If no payload exists for this model, SKIP it.
            if (empty($config)) {
                log_message('warning', "GeminiService: Skipping model '$currentModel' because no payload configuration was found.");
                continue; // Move to the next model in the priority list
            }

            $apiUrl = $config['url'];
            $requestBody = $config['body'];
            // --------------------------------------------------

            $maxRetries = 3;
            $initialDelay = 1;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $client = \Config\Services::curlrequest();
                    $response = $client->request('POST', $apiUrl, [
                        'body' => $requestBody, // Use the decoupled body
                        'headers' => ['Content-Type' => 'application/json'],
                        'timeout' => 90,
                        'connect_timeout' => 15,
                    ]);

                    $statusCode = $response->getStatusCode();
                    $responseBody = $response->getBody();

                    if ($statusCode === 429) {
                        log_message('warning', "Gemini API Quota Exceeded (429) for model '{$currentModel}' on attempt {$attempt}.");
                        $lastError = ['error' => "Quota exceeded for model '{$currentModel}'."];
                        if ($attempt < $maxRetries) {
                            sleep($initialDelay * pow(2, $attempt - 1));
                            continue;
                        } else {
                            break;
                        }
                    }

                    if ($statusCode !== 200) {
                        $errorData = json_decode($responseBody, true);
                        $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                        log_message('error', "Gemini API Error: Status {$statusCode} - {$errorMessage} | Model: {$currentModel} | Response: {$responseBody}");
                        return ['error' => $errorMessage];
                    }

                    $responseData = json_decode($responseBody, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        log_message('error', 'Gemini API Response JSON Decode Error: ' . json_last_error_msg() . ' | Response: ' . $responseBody);
                        return ['error' => 'Failed to decode API response.'];
                    }

                    $processedText = '';
                    if (isset($responseData['candidates'][0]['content']['parts'])) {
                        foreach ($responseData['candidates'][0]['content']['parts'] as $part) {
                            $processedText .= $part['text'] ?? '';
                        }
                    }

                    $usageMetadata = $responseData['usageMetadata'] ?? null;

                    if (empty($processedText) && $usageMetadata === null) {
                        return ['error' => 'Received an empty or invalid response from the AI.'];
                    }

                    return ['result' => $processedText, 'usage' => $usageMetadata];
                } catch (\Exception $e) {
                    log_message('error', "Gemini API Request Attempt {$attempt} failed for model '{$currentModel}': " . $e->getMessage());
                    $lastError = ['error' => 'The AI service is currently unavailable or the request timed out. Please try again in a few moments.'];
                    if ($attempt < $maxRetries) {
                        sleep($initialDelay * pow(2, $attempt - 1));
                    }
                }
            }
        }

        $finalErrorMsg = $lastError['error'] ?? 'An unexpected error occurred after multiple retries across all models.';
        if (str_contains($finalErrorMsg, 'Quota exceeded')) {
            return ['error' => 'All available AI models have exceeded their quota. Please wait and try again later.'];
        }

        return $lastError;
    }

    /**
     * [UPDATED] Generates raw PCM audio data from a text string using the Gemini TTS API.
     * ... (Remaining methods generateSpeech and countTokens stay as is) ...
     */
    /**
     * Generates raw PCM audio data from a text string using the Gemini TTS API.
     *
     * @param string $textToSpeak The text to convert to speech.
     * @return array ['status' => bool, 'audioData' => string|null, 'error' => string|null]
     */
    public function generateSpeech(string $textToSpeak): array
    {
        $apiKey = trim($this->apiKey);
        if (!$apiKey) {
            return ['status' => false, 'error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $ttsModel = 'gemini-2.5-flash-preview-tts';
        $apiMethod = 'streamGenerateContent';
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$ttsModel}:{$apiMethod}?key=" . urlencode($apiKey);

        $requestPayload = [
            "contents" => [
                ["role" => "user", "parts" => [["text" => $textToSpeak]]]
            ],
            "generationConfig" => [
                "responseModalities" => ["audio"],
                "speech_config" => [
                    "voice_config" => [
                        "prebuilt_voice_config" => [
                            "voice_name" => "Zephyr",
                        ]
                    ]
                ]
            ],
        ];

        try {
            $client = \Config\Services::curlrequest([
                'timeout' => 60,
                'connect_timeout' => 15,
            ]);
            $response = $client->request('POST', $apiUrl, [
                'body' => json_encode($requestPayload),
                'headers' => ['Content-Type' => 'application/json'],
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody();

            if ($statusCode !== 200) {
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData[0]['error']['message'] ?? $errorData['error']['message'] ?? 'Unknown API error during speech generation.';
                log_message('error', "Gemini TTS Error: Status {$statusCode} - {$errorMessage}");
                return ['status' => false, 'error' => $errorMessage];
            }

            $responseDataArray = json_decode($responseBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'Gemini TTS Response JSON Decode Error: ' . json_last_error_msg() . ' | Response: ' . $responseBody);
                return ['status' => false, 'error' => 'Failed to decode API speech response.'];
            }

            // Resiliently parse the response to find the audio data
            $audioData = '';
            $foundAudio = false;
            $usageMetadata = null;

            foreach ($responseDataArray as $chunk) {
                if (isset($chunk['usageMetadata'])) {
                    $usageMetadata = $chunk['usageMetadata'];
                }

                $candidates = $chunk['candidates'][0] ?? null;
                if (!$candidates) continue;

                $parts = $candidates['content']['parts'][0] ?? null;
                if (!$parts) continue;

                if (isset($parts['inlineData']['data'])) {
                    $audioData .= $parts['inlineData']['data'];
                    $foundAudio = true;
                }
            }

            if (!$foundAudio) {
                log_message('error', 'Gemini TTS Error: Audio data not found in the expected location in the response.');
                return ['status' => false, 'error' => 'Failed to retrieve audio data from the AI service.'];
            }

            return ['status' => true, 'audioData' => $audioData, 'usage' => $usageMetadata];
        } catch (\Exception $e) {
            log_message('error', 'Gemini TTS Exception: ' . $e->getMessage());
            return ['status' => false, 'error' => 'Could not connect to the speech synthesis service.'];
        }
    }

    /**
     * Counts the number of tokens in the provided parts.
     *
     * @param array $parts The content parts to count tokens for.
     * @return array ['status' => bool, 'totalTokens' => int, 'error' => string|null]
     */
    public function countTokens(array $parts): array
    {
        // This method remains unchanged and is fully functional.
        $apiKey = trim($this->apiKey);
        if (!$apiKey) {
            return ['status' => false, 'error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $currentModel = "gemini-2.0-flash"; // Updated default for token counting
        $countTokensApi = "countTokens";
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:{$countTokensApi}?key=" . urlencode($apiKey);

        $requestPayload = ["contents" => [["parts" => $parts]]];
        $client = \Config\Services::curlrequest();

        try {
            $response = $client->request('POST', $apiUrl, [
                'body' => json_encode($requestPayload),
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody();

            if ($statusCode !== 200) {
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error during token count.';
                log_message('error', "Gemini API countTokens Error: Status {$statusCode} - {$errorMessage}");
                return ['status' => false, 'error' => $errorMessage];
            }

            $responseData = json_decode($responseBody, true);
            $totalTokens = $responseData['totalTokens'] ?? 0;

            return ['status' => true, 'totalTokens' => $totalTokens];
        } catch (\Exception $e) {
            log_message('error', 'Gemini API countTokens Exception: ' . $e->getMessage());
            return ['status' => false, 'error' => 'Could not connect to the AI service to estimate cost.'];
        }
    }
}
