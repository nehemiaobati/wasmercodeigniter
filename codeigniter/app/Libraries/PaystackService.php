<?php

namespace App\Libraries;

/**
 * Provides a service layer for interacting with the Paystack payment gateway API.
 */
class PaystackService
{
    /**
     * The Paystack secret API key.
     * @var string
     */
    private string $secretKey;

    /**
     * The base URL for the Paystack API.
     * @var string
     */
    private string $baseUrl = 'https://api.paystack.co';

    /**
     * The default currency for transactions.
     * @var string
     */
    protected string $currency = 'KES';

    /**
     * Constructor.
     * Initializes the service and retrieves the Paystack secret key from environment variables.
     *
     * @throws \Exception If the Paystack secret key is not configured.
     */
    public function __construct()
    {
        $this->secretKey = env('PAYSTACK_SECRET_KEY');
        if (empty($this->secretKey)) {
            throw new \Exception('Paystack secret key is not set in .env file.');
        }
    }

    /**
     * Initializes a new payment transaction on Paystack.
     *
     * @param string      $email       The customer's email address.
     * @param int         $amount      The transaction amount in the major currency unit (e.g., KES).
     * @param string      $callbackUrl The URL to redirect to after the transaction is complete.
     * @param string|null $currency    The currency of the transaction (e.g., 'KES'). Defaults to class property.
     * @return array The API response from Paystack.
     */
    public function initializeTransaction(string $email, int $amount, string $callbackUrl, string $currency = null): array
    {
        $url = $this->baseUrl . '/transaction/initialize';
        $fields = [
            'email'        => $email,
            'amount'       => $amount * 100, // Amount in the lowest currency unit (kobo/cents)
            'callback_url' => $callbackUrl,
            'currency'     => $currency ?? $this->currency,
        ];

        return $this->sendRequest('POST', $url, $fields);
    }

    /**
     * Verifies the status of a Paystack transaction.
     *
     * @param string $reference The unique reference code for the transaction.
     * @return array The API response from Paystack.
     */
    public function verifyTransaction(string $reference): array
    {
        $url = $this->baseUrl . '/transaction/verify/' . rawurlencode($reference);

        return $this->sendRequest('GET', $url);
    }

    /**
     * Sends an HTTP request to the Paystack API.
     *
     * @param string $method The HTTP method (e.g., 'GET', 'POST').
     * @param string $url    The full URL for the API endpoint.
     * @param array  $fields The data to be sent with the request (for POST).
     * @return array The decoded JSON response from the API.
     * @throws \Exception If an error occurs during the API request.
     */
    private function sendRequest(string $method, string $url, array $fields = []): array
    {
        $client = \Config\Services::curlrequest();

        $headers = [
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type'  => 'application/json',
        ];

        try {
            if ($method === 'POST') {
                $response = $client->post($url, [
                    'headers' => $headers,
                    'json'    => $fields,
                ]);
            } else {
                $response = $client->get($url, [
                    'headers' => $headers,
                ]);
            }

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            log_message('error', 'Paystack API Error: ' . $e->getMessage());

            return [
                'status'  => false,
                'message' => 'Error communicating with Paystack: ' . $e->getMessage(),
            ];
        }
    }
}
