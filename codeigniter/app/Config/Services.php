<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use App\Libraries\EmbeddingService;
use App\Libraries\MemoryService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /*
     * public static function example($getShared = true)
     * {
     *     if ($getShared) {
     *         return static::getSharedInstance('example');
     *     }
     *
     *     return new \CodeIgniter\Example();
     * }
     */

    public static function paystackService($getShared = true): \App\Libraries\PaystackService
    {
        if ($getShared) {
            return static::getSharedInstance('paystackService');
        }

        return new \App\Libraries\PaystackService();
    }

    public static function cryptoService($getShared = true): \App\Libraries\CryptoService
    {
        if ($getShared) {
            return static::getSharedInstance('cryptoService');
        }

        return new \App\Libraries\CryptoService();
    }

    public static function geminiService($getShared = true): \App\Libraries\GeminiService
    {
        if ($getShared) {
            return static::getSharedInstance('geminiService');
        }

        return new \App\Libraries\GeminiService();
    }

    public static function recaptchaService($getShared = true): \App\Libraries\RecaptchaService
    {
        if ($getShared) {
            return static::getSharedInstance('recaptchaService');
        }

        return new \App\Libraries\RecaptchaService();
    }

    public static function embedding($getShared = true): EmbeddingService
    {
        if ($getShared) {
            return static::getSharedInstance('embedding');
        }
        return new EmbeddingService();
    }

    public static function memory(int $userId, $getShared = false): MemoryService
    {
        // This service is user-specific, so it should not be shared.
        if ($getShared) {
             // You could implement a user-specific pool if needed, but for now, we create a new instance.
            return static::getSharedInstance('memory', $userId);
        }
        return new MemoryService($userId);
    }
}
