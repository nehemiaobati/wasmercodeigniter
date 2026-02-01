<?php

declare(strict_types=1);

namespace App\Modules\Ollama\Config;

use CodeIgniter\Config\BaseService;
use App\Modules\Ollama\Libraries\OllamaService;
use App\Modules\Ollama\Libraries\OllamaMemoryService;
use App\Modules\Ollama\Libraries\OllamaDocumentService;
use App\Modules\Ollama\Libraries\OllamaTokenService;
use App\Modules\Ollama\Libraries\OllamaPayloadService;
use App\Modules\Ollama\Libraries\OllamaEmbeddingService;
use App\Modules\Ollama\Libraries\OllamaPandocService;

/**
 * Ollama Module Services Configuration.
 * 
 * Allows automatic discovery of Ollama-specific services by the framework.
 */
class Services extends BaseService
{
    /**
     * The main Ollama interaction service.
     *
     * @param bool $getShared
     * @return OllamaService
     */
    public static function ollamaService(bool $getShared = true): OllamaService
    {
        if ($getShared) {
            return static::getSharedInstance('ollamaService');
        }

        return new OllamaService();
    }

    /**
     * User-specific conversation memory for Ollama.
     *
     * @param int  $userId
     * @param bool $getShared
     * @return OllamaMemoryService
     */
    public static function ollamaMemory(int $userId, bool $getShared = false): OllamaMemoryService
    {
        if ($getShared) {
            return static::getSharedInstance('ollamaMemory', $userId);
        }

        return new OllamaMemoryService($userId);
    }

    /**
     * Document generation service for Ollama.
     *
     * @param bool $getShared
     * @return OllamaDocumentService
     */
    public static function ollamaDocumentService(bool $getShared = true): OllamaDocumentService
    {
        if ($getShared) {
            return static::getSharedInstance('ollamaDocumentService');
        }

        return new OllamaDocumentService();
    }

    /**
     * Token calculation service for Ollama.
     *
     * @param bool $getShared
     * @return OllamaTokenService
     */
    public static function ollamaTokenService(bool $getShared = true): OllamaTokenService
    {
        if ($getShared) {
            return static::getSharedInstance('ollamaTokenService');
        }

        return new OllamaTokenService();
    }

    /**
     * API Payload builder for Ollama.
     *
     * @param bool $getShared
     * @return OllamaPayloadService
     */
    public static function ollamaPayloadService(bool $getShared = true): OllamaPayloadService
    {
        if ($getShared) {
            return static::getSharedInstance('ollamaPayloadService');
        }

        return new OllamaPayloadService();
    }

    /**
     * Embedding service for Ollama.
     *
     * @param bool $getShared
     * @return OllamaEmbeddingService
     */
    public static function ollamaEmbedding(bool $getShared = true): OllamaEmbeddingService
    {
        if ($getShared) {
            return static::getSharedInstance('ollamaEmbedding');
        }

        return new OllamaEmbeddingService();
    }

    /**
     * Pandoc document conversion service.
     *
     * @param bool $getShared
     * @return OllamaPandocService
     */
    public static function ollamaPandocService(bool $getShared = true): OllamaPandocService
    {
        if ($getShared) {
            return static::getSharedInstance('ollamaPandocService');
        }

        return new OllamaPandocService();
    }
}
