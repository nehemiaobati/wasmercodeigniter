<?php

declare(strict_types=1);

namespace App\Modules\Payments\Config;

use CodeIgniter\Config\BaseService;
use App\Modules\Payments\Libraries\PaystackService;

/**
 * Payments Module Services Configuration.
 */
class Services extends BaseService
{
    /**
     * The Paystack service.
     *
     * @param bool $getShared
     * @return PaystackService
     */
    public static function paystackService(bool $getShared = true): PaystackService
    {
        if ($getShared) {
            return static::getSharedInstance('paystackService');
        }

        return new PaystackService();
    }
}
