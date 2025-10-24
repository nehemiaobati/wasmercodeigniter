<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Recaptcha extends BaseConfig
{
    public string $siteKey;
    public string $secretKey;

    public function __construct()
    {
        parent::__construct();

        $this->siteKey = env('recaptcha_siteKey');
        $this->secretKey = env('recaptcha_secretKey');
    }
}
