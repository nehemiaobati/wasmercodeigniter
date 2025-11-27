<?php

declare(strict_types=1);

namespace App\Libraries;

class RecaptchaService
{
    public function verify(string $response): bool
    {
        $secret = config('Config\Custom\Recaptcha')->secretKey;

        try {
            $client = \Config\Services::curlrequest();
            $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret'   => $secret,
                    'response' => $response,
                ],
                'verify' => false, // Equivalent to CURLOPT_SSL_VERIFYPEER false
                'timeout' => 10,
            ]);

            $result = $response->getBody();
            $status = json_decode($result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', '[RecaptchaService] JSON Decode Error: ' . json_last_error_msg());
                return false;
            }

            return ($status !== null && ($status['success'] ?? false));
        } catch (\Exception $e) {
            log_message('error', '[RecaptchaService] HTTP Request failed: ' . $e->getMessage());
            return false;
        }
    }
}
