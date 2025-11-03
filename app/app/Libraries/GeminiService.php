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
     * The service will attempt to use these models in order, falling back to the next
     * if a quota error (429) is encountered for the current model.
     * @var array<string>
     */
    protected array $modelPriorities = [
        //"gemini-2.5-pro",
        "gemini-flash-latest",
        "gemini-flash-lite-latest",
        "gemini-2.5-flash",
        "gemini-2.5-flash-lite",
        "gemini-2.0-flash",
        "gemini-2.0-flash-lite",
        // Add more fallback models here if available, e.g., "gemini-1.0-pro", "gemini-1.0-flash"
    ];

    /**
     * Constructor.
     * Initializes the service and retrieves the Gemini API key from environment variables.
     */
    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY') ?? getenv('GEMINI_API_KEY');
    }

    /**
     * Counts the number of tokens in a given set of content parts.
     * This method will use the highest priority model and does not implement fallback.
     *
     * @param array $parts An array of content parts (text and/or inlineData for files).
     * @return array An associative array with 'status' (bool) and 'totalTokens' (int) or 'error' (string).
     */
    public function countTokens(array $parts): array
    {
        if (!$this->apiKey) {
            return ['status' => false, 'error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        // Use the highest priority model for token counting
        // Ensure $modelPriorities is not empty before accessing index 0
        $currentModel = !empty($this->modelPriorities) ? $this->modelPriorities[0] : "gemini-flash-latest";
        $countTokensApi = "countTokens";
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:{$countTokensApi}?key={$this->apiKey}";

        $requestPayload = ["contents" => [["parts" => $parts]]];
        $requestBody = json_encode($requestPayload);
        $client = \Config\Services::curlrequest();

        try {
            $response = $client->request('POST', $apiUrl, [
                'body' => $requestBody,
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

    /**
     * Sends a request to the Gemini API with a retry mechanism and model fallback for quota errors.
     *
     * @param array $parts An array of content parts (text and/or inlineData for files).
     * @return array An associative array with either a 'result' string and 'usage' data on success, or an 'error' string on failure.
     */
    public function generateContent(array $parts): array
    {
        if (!$this->apiKey) {
            return ['error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $generateContentApi = "generateContent";
        $lastError = ['error' => 'An unexpected error occurred after multiple retries.']; // Default error, overridden by specific errors

        if (empty($this->modelPriorities)) {
            return ['error' => 'No Gemini models configured in modelPriorities.'];
        }

        foreach ($this->modelPriorities as $model) {
            $currentModel = $model;
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:{$generateContentApi}?key={$this->apiKey}";

            // MODIFIED: Added tools and thinkingConfig to the payload
            $requestPayload = [
                "contents" => [["role" => "user", "parts" => $parts]],
                "generationConfig" => [
                    "maxOutputTokens" => 64192,
                    "thinkingConfig" => [
                        "thinkingBudget" => -1
                    ]
                ],
                "tools" => [
                    ["googleSearch" => new \stdClass()]
                ],
            ];

            $maxRetries = 3;
            $initialDelay = 1; // seconds

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $client = \Config\Services::curlrequest();
                    $response = $client->request('POST', $apiUrl, [
                        'body' => json_encode($requestPayload),
                        'headers' => ['Content-Type' => 'application/json'],
                        'timeout' => 90, // Increased timeout to 90 seconds for large files
                        'connect_timeout' => 15,
                    ]);

                    $statusCode = $response->getStatusCode();
                    $responseBody = $response->getBody();

                    if ($statusCode === 429) {
                        // Quota exceeded for this model. Log and decide whether to retry this model or try fallback.
                        log_message('debug',"Try model {$currentModel} attempt {$attempt}");
                        log_message('warning', "Gemini API Quota Exceeded (429) for model '{$currentModel}' on attempt {$attempt}.");
                        $lastError = ['error' => "Quota exceeded for model '{$currentModel}'."];

                        if ($attempt < $maxRetries) {
                            // Retry this model after exponential backoff
                            sleep($initialDelay * pow(2, $attempt - 1));
                            continue; // Continue to the next retry attempt for the SAME model
                        } else {
                            // Max retries for this specific model exhausted due to 429.
                            // Break from inner retry loop to proceed to the next model in $modelPriorities.
                            break;
                        }
                    }

                    // For any other non-200 error, or if successful, return immediately.
                    if ($statusCode !== 200) {
                        $errorData = json_decode($responseBody, true);
                        $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                        log_message('error', "Gemini API Error: Status {$statusCode} - {$errorMessage} | Model: {$currentModel} | Response: {$responseBody}");
                        $lastError = ['error' => $errorMessage];
                        return $lastError; // Non-429 error, so fail immediately without further fallback.
                    }

                    $responseData = json_decode($responseBody, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        log_message('error', 'Gemini API Response JSON Decode Error: ' . json_last_error_msg() . ' | Response: ' . $responseBody);
                        $lastError = ['error' => 'Failed to decode API response.'];
                        return $lastError; // JSON decode error, fail immediately.
                    }

                    $processedText = '';
                    if (isset($responseData['candidates'][0]['content']['parts'])) {
                        foreach ($responseData['candidates'][0]['content']['parts'] as $part) {
                            $processedText .= $part['text'] ?? '';
                        }
                    }

                    $usageMetadata = $responseData['usageMetadata'] ?? null;

                    if (empty($processedText) && $usageMetadata === null) {
                        $lastError = ['error' => 'Received an empty or invalid response from the AI.'];
                        return $lastError; // Empty response, fail immediately.
                    }

                    // Success! Return the result and exit both loops.
                    return ['result' => $processedText, 'usage' => $usageMetadata];

                } catch (\Exception $e) {
                    // This catch block handles network errors or unexpected exceptions during the cURL request.
                    log_message('error', "Gemini API Request Attempt {$attempt} failed for model '{$currentModel}': " . $e->getMessage());
                    $lastError = ['error' => 'The AI service is currently unavailable or the request timed out. Please try again in a few moments.'];

                    if ($attempt < $maxRetries) {
                        sleep($initialDelay * pow(2, $attempt - 1));
                        continue; // Continue to the next retry attempt for the SAME model
                    } else {
                        // Max retries for this specific model exhausted due to a network error.
                        // Break from inner retry loop to proceed to the next model in $modelPriorities.
                        break;
                    }
                }
            }
            // If we reach here, it means the current model failed after all retries (likely 429 or network error)
            // and we continue to the next model in the foreach loop.
        }

        // If the foreach loop completes, it means all models have been tried.
        // Check if the last error was specifically a 429 across all models.
        $finalErrorMsg = $lastError['error'] ?? 'An unexpected error occurred after multiple retries across all models.';
        if (str_contains($finalErrorMsg, 'Quota exceeded')) {
            return ['error' => 'All available AI models have exceeded their quota. Please wait and try again later. To increase your limits, request a quota increase through AI Studio, or switch to another /auth method.'];
        }

        return $lastError;
    }
}