<?php

namespace App\Libraries;

class RecaptchaService
{
    public function verify(string $response): bool
    {
        $secret = config('Recaptcha')->secretKey;
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

        $status = json_decode($result, true);

        return $status['success'] ?? false;
    }
}
