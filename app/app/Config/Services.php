<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use App\Libraries\EmbeddingService;
use App\Libraries\MemoryService;
use App\Libraries\TokenService;
use App\Libraries\TrainingService;
use App\Libraries\PaystackService;
use App\Libraries\CryptoService;
use App\Libraries\GeminiService;
use App\Libraries\RecaptchaService;
use App\Libraries\FfmpegService;
use App\Libraries\PandocService;
use App\Libraries\DocumentService;

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

    /**
     * The Gemini service.
     *
     * @param bool $getShared
     * @return GeminiService
     */
    public static function geminiService(bool $getShared = true): GeminiService
    {
        if ($getShared) {
            return static::getSharedInstance('geminiService');
        }

        return new GeminiService();
    }

    /**
     * The Recaptcha service.
     *
     * @param bool $getShared
     * @return RecaptchaService
     */
    public static function recaptchaService(bool $getShared = true): RecaptchaService
    {
        if ($getShared) {
            return static::getSharedInstance('recaptchaService');
        }

        return new RecaptchaService();
    }

    /**
     * The Embedding service.
     *
     * @param bool $getShared
     * @return EmbeddingService
     */
    public static function embedding(bool $getShared = true): EmbeddingService
    {
        if ($getShared) {
            return static::getSharedInstance('embedding');
        }
        return new EmbeddingService();
    }

    /**
     * The Memory service, which is user-specific.
     * NOTE: This service defaults to creating a NEW instance on each call.
     * To get a shared instance for a specific user, you must explicitly pass
     * true as the second argument (e.g., service('memory', $userId, true)).
     *
     * @param int  $userId
     * @param bool $getShared
     * @return MemoryService
     */
    public static function memory(int $userId, bool $getShared = false): MemoryService
    {
        // This service is user-specific, so it defaults to not being shared.
        if ($getShared) {
            // A user-specific shared instance can be retrieved if requested.
            return static::getSharedInstance('memory', $userId);
        }
        return new MemoryService($userId);
    }

    /**
     * The Token service.
     *
     * @param bool $getShared
     * @return TokenService
     */
    public static function tokenService(bool $getShared = true): TokenService
    {
        if ($getShared) {
            return static::getSharedInstance('tokenService');
        }
        return new TokenService();
    }

    /**
     * The Training service.
     *
     * @param bool $getShared
     * @return TrainingService
     */
    public static function trainingService(bool $getShared = true): TrainingService
    {
        if ($getShared) {
            return static::getSharedInstance('trainingService');
        }
        return new TrainingService();
    }

    /**
     * The FFmpeg service for audio conversion.
     *
     * @param bool $getShared
     * @return FfmpegService
     */
    public static function ffmpegService(bool $getShared = true): FfmpegService
    {
        if ($getShared) {
            return static::getSharedInstance('ffmpegService');
        }
        return new FfmpegService();
    }

    /**
     * The Pandoc service for document conversion.
     *
     * @param bool $getShared
     * @return PandocService
     */
    public static function pandocService(bool $getShared = true): PandocService
    {
        if ($getShared) {
            return static::getSharedInstance('pandocService');
        }
        return new PandocService();
    }

    /**
     * The main document generation service with fallback.
     *
     * @param bool $getShared
     * @return DocumentService
     */
    public static function documentService(bool $getShared = true): DocumentService
    {
        if ($getShared) {
            return static::getSharedInstance('documentService');
        }
        return new DocumentService();
    }
}