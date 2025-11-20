<?php declare(strict_types=1);

namespace App\Modules\Ollama\Config;

use CodeIgniter\Config\BaseConfig;

class Ollama extends BaseConfig
{
    /**
     * The base URL where Ollama is running.
     * Typically localhost:11434
     */
    public string $baseUrl = 'http://127.0.0.1:11434';

    /**
     * The primary model to use for chat generation.
     * Target: deepseek-r1:1.5b
     */
    public string $chatModel = 'deepseek-r1:1.5b';

    /**
     * The model to use for generating embeddings.
     * nomic-embed-text is excellent and lightweight, 
     * but you can use the chat model if it supports embeddings.
     */
    //public string $embeddingModel = 'nomic-embed-text';
    public string $embeddingModel = 'deepseek-r1:1.5b';


    /**
     * Request timeout in seconds. 
     * Local models can be slow on CPU.
     */
    public int $timeout = 120;

    /**
     * Context Window (History)
     * How many previous interactions to include in the prompt.
     */
    public int $historyDepth = 1;
    
    /**
     * Vector Search Threshold
     * Minimum similarity score (0.0 to 1.0) to include a memory.
     */
    public float $similarityThreshold = 0.65;
}
