<?php declare(strict_types=1);

namespace App\Libraries;

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
     * An ordered list of Gemini model IDs to try, from most preferred to least preferred.
     * @var array<string>
     */
    protected array $modelPriorities = [
        "gemini-flash-latest",
        "gemini-flash-lite-latest",
        "gemini-2.5-flash",
        "gemini-2.5-flash-lite",
        "gemini-2.0-flash",
        "gemini-2.0-flash-lite",
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY') ?? getenv('GEMINI_API_KEY');
    }

    /**
     * Sends a request to the Gemini API for text generation.
     * @param array $parts An array of content parts.
     * @return array An associative array with 'result' or 'error'.
     */
    public function generateContent(array $parts): array
    {
        // This method remains unchanged and is fully functional.
        if (!$this->apiKey) {
            return ['error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $apiKey = trim($this->apiKey);
        $generateContentApi = "generateContent";
        $lastError = ['error' => 'An unexpected error occurred after multiple retries.'];

        if (empty($this->modelPriorities)) {
            return ['error' => 'No Gemini models configured in modelPriorities.'];
        }

        foreach ($this->modelPriorities as $model) {
            $currentModel = $model;
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:{$generateContentApi}?key=" . urlencode($apiKey);

            $requestPayload = [
                "contents" => [["role" => "user", "parts" => $parts]],
                "generationConfig" => [
                    "maxOutputTokens" => 64192,
                    "thinkingConfig" => ["thinkingBudget" => -1]
                ],
                "tools" => [["googleSearch" => new \stdClass()]],
            ];

            $maxRetries = 3;
            $initialDelay = 1;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $client = \Config\Services::curlrequest();
                    $response = $client->request('POST', $apiUrl, [
                        'body' => json_encode($requestPayload),
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
     *
     * @param string $textToSpeak The text to be converted to speech.
     * @return array An associative array with 'status' (bool) and 'audioData' (string|null) or 'error' (string).
     *               The 'audioData' is base64-encoded raw PCM audio.
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
            $audioData = null;
            foreach ($responseDataArray as $chunk) {
                $parts = $chunk['candidates'][0]['content']['parts'][0] ?? null;
                if (!$parts) continue;
                
                if (isset($parts['inlineData']['data'])) {
                    $audioData = $parts['inlineData']['data'];
                    break;
                }
            }

            if ($audioData === null) {
                log_message('error', 'Gemini TTS Error: Audio data not found in the expected location in the response.');
                return ['status' => false, 'error' => 'Failed to retrieve audio data from the AI service.'];
            }

            return ['status' => true, 'audioData' => $audioData];

        } catch (\Exception $e) {
            log_message('error', 'Gemini TTS Exception: ' . $e->getMessage());
            return ['status' => false, 'error' => 'Could not connect to the speech synthesis service.'];
        }
    }

    /**
     * Counts the number of tokens in a given set of content parts.
     * @param array $parts An array of content parts.
     * @return array An associative array with 'status' and 'totalTokens' or 'error'.
     */
    public function countTokens(array $parts): array
    {
        // This method remains unchanged and is fully functional.
        $apiKey = trim($this->apiKey);
        if (!$apiKey) {
            return ['status' => false, 'error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $currentModel = $this->modelPriorities[0] ?? "gemini-1.5-flash-latest";
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