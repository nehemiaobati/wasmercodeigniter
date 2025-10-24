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
     * The model ID to use for API calls.
     * @var string
     */
    protected string $modelId = "gemini-flash-latest"; // Centralize model ID

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
     *
     * @param array $parts An array of content parts (text and/or inlineData for files).
     * @return array An associative array with 'status' (bool) and 'totalTokens' (int) or 'error' (string).
     */
    public function countTokens(array $parts): array
    {
        if (!$this->apiKey) {
            return ['status' => false, 'error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $countTokensApi = "countTokens";
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->modelId}:{$countTokensApi}?key={$this->apiKey}";

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
     * Sends a request to the Gemini API with a retry mechanism for transient errors.
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
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->modelId}:{$generateContentApi}?key={$this->apiKey}";

        $requestPayload = [
            "contents" => [["role" => "user", "parts" => $parts]],
            "generationConfig" => ["maxOutputTokens" => 8192], // Adjusted for flash model
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

                // If the status is 503, it's a server error, so we should retry.
                if ($statusCode === 503) {
                    throw new \Exception("Service Unavailable (503) - Retrying...", 503);
                }

                // If the status is not 200, it's a client or other server error, fail immediately.
                if ($statusCode !== 200) {
                    $errorData = json_decode($responseBody, true);
                    $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                    log_message('error', "Gemini API Error: Status {$statusCode} - {$errorMessage} | Response: {$responseBody}");
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

                // Success, return the result
                return ['result' => $processedText, 'usage' => $usageMetadata];

            } catch (\Exception $e) {
                // LOG THE TECHNICAL ERROR for debugging
                log_message('error', "Gemini API Request Attempt {$attempt} failed: " . $e->getMessage());

                // If this was the last attempt, RETURN A USER-FRIENDLY ERROR to the UI
                if ($attempt === $maxRetries) {
                    return ['error' => 'The AI service is currently unavailable or the request timed out. Please try again in a few moments.'];
                }

                // Wait before the next attempt (exponential backoff)
                sleep($initialDelay * pow(2, $attempt - 1));
            }
        }
        
        // This should not be reached, but as a fallback
        return ['error' => 'An unexpected error occurred after multiple retries.'];
    }
}