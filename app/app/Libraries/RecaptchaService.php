<?php declare(strict_types=1);

namespace App\Libraries;

class RecaptchaService
{
    public function verify(string $response): bool
    {
        $secret = config('Config\Custom\Recaptcha')->secretKey;
        $credential = [
            'secret'   => $secret,
            'response' => $response,
        ];

        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($credential));
        curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($verify);
        $error  = curl_error($verify);
        $errno  = curl_errno($verify);
        curl_close($verify); // Always close the cURL handle

        if ($result === false) {
            log_message('error', "[RecaptchaService] cURL error: ({$errno}) {$error}");
            return false; // Network error or other cURL failure
        }

        $status = json_decode($result, true);

        // Check if json_decode failed or if 'success' key is missing/false
        return ($status !== null && ($status['success'] ?? false));
    }
}
