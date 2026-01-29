<?php

declare(strict_types=1);

namespace App\Modules\Payments\Libraries;

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
    private bool $isConfigured = false;

    public function __construct()
    {
        $this->secretKey = env('PAYSTACK_SECRET_KEY');
        if (!empty($this->secretKey)) {
            $this->isConfigured = true;
        } else {
            log_message('critical', '[PaystackService] Paystack secret key is not set in .env file. PaystackService will be unavailable.');
        }
    }

    /**
     * Checks if the PaystackService is configured with a secret key.
     *
     * @return bool True if configured, false otherwise.
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
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
    public function initializeTransaction(string $email, int $amount, string $callbackUrl, ?string $currency = null): array
    {
        if (! $this->isConfigured()) {
            return ['status' => false, 'message' => 'Payment provider is not configured.'];
        }

        $url = $this->baseUrl . '/transaction/initialize';
        $fields = [
            'email'        => $email,
            'amount'       => $amount * 100, // Amount in the lowest currency unit (kobo/cents)
            'callback_url' => $callbackUrl,
            'currency'     => $currency ?? $this->currency,
        ];

        return $this->_sendRequest('POST', $url, $fields);
    }

    /**
     * Verifies the status of a Paystack transaction.
     *
     * @param string $reference The unique reference code for the transaction.
     * @return array The API response from Paystack.
     */
    public function verifyTransaction(string $reference): array
    {
        if (! $this->isConfigured()) {
            return ['status' => false, 'message' => 'Payment provider is not configured.'];
        }

        $url = $this->baseUrl . '/transaction/verify/' . rawurlencode($reference);

        return $this->_sendRequest('GET', $url);
    }

    /**
     * Verifies the status of a Paystack transaction and processes the outcome.
     * Orchestrates database updates, user balance adjustments, and bonus awarding.
     *
     * @param string $appReference      The internal application reference.
     * @param string $paystackReference The Paystack transaction reference.
     * @return array ['status' => bool, 'message' => string]
     */
    public function verifyAndProcessPayment(string $appReference, string $paystackReference): array
    {
        if (!$this->isConfigured()) {
            return ['status' => false, 'message' => 'Payment provider is not configured.'];
        }

        $paymentModel = new \App\Modules\Payments\Models\PaymentModel();
        $payment = $paymentModel->where('reference', $appReference)->first();

        if ($payment === null) {
            return ['status' => false, 'message' => 'Invalid payment reference.'];
        }

        if ($payment->status === 'success') {
            return ['status' => true, 'message' => 'Payment already verified.'];
        }

        $response = $this->verifyTransaction($paystackReference);
        $isSuccess = ($response['status'] === true && isset($response['data']['status']) && $response['data']['status'] === 'success');

        $db = \Config\Database::connect();
        $db->transStart();

        $status = $isSuccess ? 'success' : 'failed';
        $jsonResponse = json_encode($response['data'] ?? $response) ?: json_encode(['error' => 'JSON encoding failed']);

        $paymentModel->update($payment->id, [
            'status'            => $status,
            'paystack_response' => $jsonResponse,
        ]);

        if ($isSuccess) {
            $userModel = new \App\Models\UserModel();

            // Award first deposit bonus
            $this->_awardFirstDepositBonus((int) $payment->user_id, (int) $payment->id);

            // Update user balance
            if ($payment->user_id) {
                $userModel->addBalance((int) $payment->user_id, (string) $payment->amount);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('critical', "[PaystackService] Payment processing transaction failed for reference: {$appReference}");
            return ['status' => false, 'message' => 'A critical error occurred while processing your payment.'];
        }

        return [
            'status'  => $isSuccess,
            'message' => $isSuccess ? 'Payment successful!' : ($response['message'] ?? 'Payment verification failed.'),
        ];
    }



    // --- Helper Methods ---

    /**
     * Awards a bonus for the user's first successful deposit.
     *
     * @param int $userId           The user ID.
     * @param int $currentPaymentId The current payment ID to exclude from previous checks.
     * @return void
     */
    private function _awardFirstDepositBonus(int $userId, int $currentPaymentId): void
    {
        $paymentModel = new \App\Modules\Payments\Models\PaymentModel();
        $userModel = new \App\Models\UserModel();

        $hasPriorPayments = $paymentModel
            ->where('user_id', $userId)
            ->where('status', 'success')
            ->where('id !=', $currentPaymentId)
            ->countAllResults() > 0;

        if (!$hasPriorPayments) {
            $bonusAmount = '30.00'; // 30.00 KSH bonus for first deposit
            $userModel->addBalance($userId, $bonusAmount);
            log_message('info', "[PaystackService] First deposit bonus (KSH {$bonusAmount}) awarded to User ID: {$userId}");
        }
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
    private function _sendRequest(string $method, string $url, array $fields = []): array
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
                    'http_errors' => false,
                ]);
            } else {
                $response = $client->get($url, [
                    'headers' => $headers,
                    'http_errors' => false,
                ]);
            }

            $body = $response->getBody();
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                log_message('error', "[PaystackService] HTTP {$statusCode} error. URL: {$url}. Method: {$method}. Response: {$body}");
            }

            return json_decode($body, true) ?? ['status' => false, 'message' => 'Malformed response from API'];
        } catch (\Exception $e) {
            log_message('error', "[PaystackService] API Exception. URL: {$url}. Method: {$method}. Error: " . $e->getMessage());

            return [
                'status'  => false,
                'message' => 'Error communicating with Paystack: ' . $e->getMessage(),
            ];
        }
    }
}
