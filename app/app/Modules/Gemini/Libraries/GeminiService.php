<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use App\Modules\Gemini\Libraries\ModelPayloadService;

/**
 * Service layer for interacting with the Google Gemini API.
 *
 * This service handles:
 * - Text generation using various Gemini models.
 * - Text-to-Speech (TTS) generation.
 * - Token counting and cost estimation.
 * - Cost calculation based on usage metadata.
 * - Payload configuration management via ModelPayloadService.
 */
class GeminiService
{
    /**
     * The API key for authenticating with the Gemini API.
     * @var string|null
     */
    protected $apiKey;

    /**
     * Reference to the payload configuration service.
     * @var ModelPayloadService
     */
    protected $payloadService;

    /**
     * An ordered list of Gemini model IDs to try, from most preferred to least preferred.
     * This fallback mechanism ensures high availability even if specific models are rate-limited.
     *
     * @var array<string>
     */
    protected array $modelPriorities = [
        "gemini-3-pro-preview", // Multimodal, Thinking Level High
        "gemini-2.5-pro",       // Multimodal, Thinking Budget
        "gemini-flash-latest",      // Primary: Latest Flash model for speed and efficiency
        "gemini-flash-lite-latest", // Secondary: Lite version for lower latency
        "gemini-2.5-flash",         // Fallback: Stable Flash version
        "gemini-2.5-flash-lite",    // Fallback: Stable Lite version
        "gemini-2.0-flash",         // Legacy Fallback
        "gemini-2.0-flash-lite",    // Legacy Fallback
    ];

    /**
     * Reference to the User Model for balance updates.
     * @var \App\Models\UserModel
     */
    protected $userModel;

    /**
     * Pricing configuration for text and audio models (per 1 Million tokens).
     *
     * Text Pricing:
     * - Tier 1 (<= 200k tokens): Lower rate for standard context windows.
     * - Tier 2 (> 200k tokens): Higher rate for long-context processing.
     *
     * Audio Pricing:
     * - Fixed rate for input and output audio tokens.
     */
    protected array $pricingConfig = [
        'default' => [
            'tier1' => ['input' => 2.00, 'output' => 12.00],
            'tier2' => ['input' => 4.00, 'output' => 18.00],
            'tier_threshold' => 200000
        ],
        'audio' => [
            'input' => 0.50,
            'output' => 10.00
        ]
    ];

    /**
     * Constructor.
     * Initializes dependencies and loads the API key from the environment.
     */
    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY') ?? getenv('GEMINI_API_KEY');
        $this->payloadService = service('modelPayloadService');
        $this->userModel = new \App\Models\UserModel();
    }

    /**
     * Sends a request to the Gemini API for text generation.
     *
     * Iterates through the configured model priorities. If a model fails due to
     * quota limits (429), it automatically retries with exponential backoff
     * or fails over to the next available model.
     *
     * @param array $parts An array of content parts (text, images, files).
     * @return array An associative array containing:
     *               - 'result' (string): The generated text.
     *               - 'usage' (array|null): Token usage metadata.
     *               - 'error' (string): Error message if the operation failed.
     */
    public function generateContent(array $parts): array
    {
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

            // Retrieve the specific payload configuration for the current model
            $config = $this->payloadService->getPayloadConfig($currentModel, $apiKey, $parts);

            // Skip if no valid configuration exists for this model
            if (empty($config)) {
                log_message('warning', "GeminiService: Skipping model '$currentModel' because no payload configuration was found.");
                continue;
            }

            $apiUrl = $config['url'];
            $requestBody = $config['body'];

            $maxRetries = 3;
            $initialDelay = 1;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $client = \Config\Services::curlrequest();
                    $response = $client->request('POST', $apiUrl, [
                        'body' => $requestBody,
                        'headers' => ['Content-Type' => 'application/json'],
                        'timeout' => 90,
                        'connect_timeout' => 15,
                    ]);

                    $statusCode = $response->getStatusCode();
                    $responseBody = $response->getBody();

                    // Handle Rate Limiting (Quota Exceeded)
                    if ($statusCode === 429) {
                        log_message('warning', "Gemini API Quota Exceeded (429) for model '{$currentModel}' on attempt {$attempt}.");
                        $lastError = ['error' => "Quota exceeded for model '{$currentModel}'."];
                        if ($attempt < $maxRetries) {
                            sleep($initialDelay * pow(2, $attempt - 1)); // Exponential backoff
                            continue;
                        } else {
                            break; // Try next model
                        }
                    }

                    // Handle General API Errors
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

                    // Extract Generated Text
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

        // Final error handling if all models fail
        $finalErrorMsg = $lastError['error'] ?? 'An unexpected error occurred after multiple retries across all models.';
        if (str_contains($finalErrorMsg, 'Quota exceeded')) {
            return ['error' => 'All available AI models have exceeded their quota. Please wait and try again later.'];
        }

        return $lastError;
    }

    /**
     * Generates raw PCM audio data from a text string using the Gemini TTS API.
     *
     * Uses the 'gemini-2.5-flash-preview-tts' model to synthesize speech.
     * The output is raw audio data that needs to be processed (e.g., by FFmpeg)
     * before being played in a browser.
     *
     * @param string $textToSpeak The text content to convert to speech.
     * @return array An associative array containing:
     *               - 'status' (bool): Success status.
     *               - 'audioData' (string|null): Base64 encoded raw audio data.
     *               - 'usage' (array|null): Token usage metadata for the audio generation.
     *               - 'error' (string|null): Error message on failure.
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

            // Parse the streamed response chunks to aggregate audio data
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
     * Counts the number of tokens in the provided content parts.
     *
     * Useful for pre-request validation and cost estimation.
     *
     * @param array $parts The content parts to count tokens for.
     * @return array ['status' => bool, 'totalTokens' => int, 'error' => string|null]
     */
    public function countTokens(array $parts): array
    {
        $apiKey = trim($this->apiKey);
        if (!$apiKey) {
            return ['status' => false, 'error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $currentModel = "gemini-2.0-flash"; // Default model for token counting
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
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'Gemini API countTokens JSON Decode Error: ' . json_last_error_msg());
                return ['status' => false, 'error' => 'Failed to decode API response.'];
            }
            $totalTokens = $responseData['totalTokens'] ?? 0;

            return ['status' => true, 'totalTokens' => $totalTokens];
        } catch (\Exception $e) {
            log_message('error', 'Gemini API countTokens Exception: ' . $e->getMessage());
            return ['status' => false, 'error' => 'Could not connect to the AI service to estimate cost.'];
        }
    }

    /**
     * Estimates the cost of a request based on input tokens.
     *
     * This method is used by the controller to check if the user has sufficient balance
     * *before* making the actual generation request. It uses Tier 1 pricing for a conservative estimate.
     *
     * @param array $parts The content parts to estimate.
     * @return array ['status' => bool, 'costKSH' => float, 'totalTokens' => int, 'error' => string]
     */
    public function estimateCost(array $parts): array
    {
        $response = $this->countTokens($parts);
        if (!$response['status']) {
            return ['status' => false, 'error' => $response['error']];
        }

        $estimatedTokens = $response['totalTokens'];

        // Use Tier 1 pricing for estimation (conservative approach)
        $pricing = $this->pricingConfig['default']['tier1'];

        $estimatedCostUSD = ($estimatedTokens / 1000000) * $pricing['input'];
        $usdToKsh = 129; // Fixed exchange rate
        $estimatedCostKSH = $estimatedCostUSD * $usdToKsh;

        return [
            'status' => true,
            'costKSH' => $estimatedCostKSH,
            'totalTokens' => $estimatedTokens
        ];
    }

    /**
     * Calculates the actual cost based on usage metadata for text and optional audio.
     *
     * This method aggregates costs from both text generation (input/output tokens)
     * and audio generation (if applicable). It handles tiered pricing for text.
     *
     * @param array $textUsage Usage metadata from the text generation API.
     * @param array|null $audioUsage Usage metadata from the audio generation API (optional).
     * @return array An associative array containing:
     *               - 'costUSD' (float): Total cost in USD.
     *               - 'costKSH' (float): Total cost in KSH.
     *               - 'tokens' (int): Total combined tokens.
     *               - 'promptTokens' (int): Text prompt tokens.
     *               - 'candidatesTokens' (int): Text output tokens.
     */
    public function calculateCost(array $textUsage, ?array $audioUsage = null): array
    {
        // 1. Calculate Text Generation Cost
        $promptTokens = $textUsage['promptTokenCount'] ?? 0;
        $candidatesTokens = $textUsage['candidatesTokenCount'] ?? 0;
        $totalTextTokens = $textUsage['totalTokenCount'] ?? ($promptTokens + $candidatesTokens);

        $pricing = $this->pricingConfig['default'];
        // Determine pricing tier based on total text tokens
        $tier = ($totalTextTokens > $pricing['tier_threshold']) ? 'tier2' : 'tier1';
        $rates = $pricing[$tier];

        $textInputCost = ($promptTokens / 1000000) * $rates['input'];
        $textOutputCost = ($candidatesTokens / 1000000) * $rates['output'];
        $totalTextCostUSD = $textInputCost + $textOutputCost;

        // 2. Calculate Audio Generation Cost (if applicable)
        $totalAudioCostUSD = 0;
        $audioTokens = 0;

        if ($audioUsage) {
            $audioInputTokens = $audioUsage['promptTokenCount'] ?? 0;
            $audioOutputTokens = $audioUsage['candidatesTokenCount'] ?? 0;
            $audioTokens = $audioUsage['totalTokenCount'] ?? ($audioInputTokens + $audioOutputTokens);

            $audioPricing = $this->pricingConfig['audio'];
            $audioInputCost = ($audioInputTokens / 1000000) * $audioPricing['input'];
            $audioOutputCost = ($audioOutputTokens / 1000000) * $audioPricing['output'];
            $totalAudioCostUSD = $audioInputCost + $audioOutputCost;
        }

        // 3. Aggregate Total Costs
        $totalCostUSD = $totalTextCostUSD + $totalAudioCostUSD;
        $usdToKsh = 129;
        $totalCostKsh = $totalCostUSD * $usdToKsh;
        $totalTokens = $totalTextTokens + $audioTokens;

        return [
            'costUSD' => $totalCostUSD,
            'costKSH' => $totalCostKsh,
            'tokens' => $totalTokens,
            'promptTokens' => $promptTokens,
            'candidatesTokens' => $candidatesTokens
        ];
    }
}
