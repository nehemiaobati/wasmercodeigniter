<?php

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
     * Constructor.
     * Initializes the service and retrieves the Gemini API key from environment variables.
     */
    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY') ?? getenv('GEMINI_API_KEY');
    }

    /**
     * Sends a request to the Gemini API to generate content based on text and media parts.
     *
     * @param array $parts An array of content parts (text and/or inlineData for files).
     * @return array An associative array with either a 'result' string on success or an 'error' string on failure.
     * @throws \Exception If an error occurs during the API request processing.
     */
    public function generateContent(array $parts): array
    {
        if (!$this->apiKey) {
            return ['error' => 'GEMINI_API_KEY not set in .env file.'];
        }

        $modelId = "gemini-flash-lite-latest";
        $generateContentApi = "streamGenerateContent";

        $requestPayload = [
            "contents" => [
                [
                    "role" => "user",
                    "parts" => $parts
                ]
            ],
            "generationConfig" => [
                "maxOutputTokens" => 15536,
                "thinkingConfig" => [
                    "thinkingBudget" => -1,
                ],
            ],
            "tools" => [
                [
                    "googleSearch" => (object)[]
                ]
            ],
        ];

        $requestBody = json_encode($requestPayload, JSON_PRETTY_PRINT);
        
        // Logs the full request payload. Consider disabling or restricting in production.
        $logFilePath = WRITEPATH . 'logs/gemini_payload.log';
        file_put_contents($logFilePath, "--- Request Payload (" . date('Y-m-d H:i:s') . ") ---\n", FILE_APPEND);
        file_put_contents($logFilePath, $requestBody . "\n\n", FILE_APPEND);

        $client = \Config\Services::curlrequest();

        try {
            $response = $client->setBody($requestBody)
                               ->setHeader('Content-Type', 'application/json')
                               ->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelId}:{$generateContentApi}?key={$this->apiKey}");

            $responseBody = $response->getBody();
            $responseData = json_decode($responseBody, true);

            if ($response->getStatusCode() !== 200) {
                $errorMessage = $responseData['error']['message'] ?? 'Unknown API error';
                return ['error' => $errorMessage];
            }

            $processedText = '';
            if (is_array($responseData)) {
                foreach ($responseData as $chunk) {
                    if (isset($chunk['candidates']) && is_array($chunk['candidates'])) {
                        foreach ($chunk['candidates'] as $candidate) {
                            if (isset($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
                                foreach ($candidate['content']['parts'] as $part) {
                                    if (isset($part['text'])) {
                                        $processedText .= $part['text'];
                                    }
                                }
                            }
                        }
                    }
                }
            } elseif (isset($responseData['candidates']) && is_array($responseData['candidates'])) {
                foreach ($responseData['candidates'] as $candidate) {
                    if (isset($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
                        foreach ($candidate['content']['parts'] as $part) {
                            if (isset($part['text'])) {
                                $processedText .= $part['text'];
                            }
                        }
                    }
                }
            }

            return ['result' => $processedText];

        } catch (\Exception $e) {
            // Log the exception and return an error structure
            log_message('error', 'Gemini API Request Exception: ' . $e->getMessage());
            return ['error' => 'An error occurred while processing your request: ' . $e->getMessage()];
        }
    }
}
