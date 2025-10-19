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
     * Sends a request to the Gemini API to generate content based on text and media parts.
     *
     * @param array $parts An array of content parts (text and/or inlineData for files).
     * @return array An associative array with either a 'result' string and 'usage' data on success, or an 'error' string on failure.
     * @throws \Exception If an error occurs during the API request processing.
     */
    public function generateContent(array $parts): array
    {
        if (!$this->apiKey) {
            return ['error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $generateContentApi = "generateContent"; // Changed to non-streaming endpoint

        $requestPayload = [
            "contents" => [
                [
                    "role" => "user",
                    "parts" => $parts
                ]
            ],
            "generationConfig" => [
                "maxOutputTokens" => 65192,
            ],
            "tools" => [
                [
                    "googleSearch" => (object)[]
                ]
            ],
        ];

        $requestBody = json_encode($requestPayload);
        $client = \Config\Services::curlrequest();

        try {
            // Added timeout and connect_timeout options
            $response = $client->request('POST', "https://generativelanguage.googleapis.com/v1beta/models/{$this->modelId}:{$generateContentApi}?key={$this->apiKey}", [
                'body' => $requestBody,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 30, // 30 seconds timeout
                'connect_timeout' => 10, // 10 seconds connect timeout
            ]);

            $responseBody = $response->getBody();
            $statusCode = $response->getStatusCode();
            
            if ($statusCode !== 200) {
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                log_message('error', "Gemini API Error: Status {$statusCode} - {$errorMessage} | Response: {$responseBody}");
                return ['error' => $errorMessage];
            }

            // Process non-streamed response
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
            log_message('error', 'Gemini API Request Exception: ' . $e->getMessage());
            return ['error' => 'An error occurred while processing your request: ' . $e->getMessage()];
        }
    }
}
