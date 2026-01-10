<?php

declare(strict_types=1);

namespace App\Modules\Crypto\Config;

use CodeIgniter\Config\BaseService;
use App\Modules\Crypto\Libraries\CryptoService;

/**
 * Crypto Module Services Configuration.
 */
class Services extends BaseService
{
    /**
     * The Crypto service.
     *
     * @param bool $getShared
     * @return CryptoService
     */
    public static function cryptoService(bool $getShared = true): CryptoService
    {
        if ($getShared) {
            return static::getSharedInstance('cryptoService');
        }

        return new CryptoService();
    }
}
