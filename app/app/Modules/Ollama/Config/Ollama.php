<?php

namespace App\Modules\Ollama\Config;

use CodeIgniter\Config\BaseConfig;

class Ollama extends BaseConfig
{
    /**
     * The base URL for the Ollama instance.
     * Default: http://localhost:11434
     */
    public string $baseUrl = 'http://localhost:11434';

    /**
     * The default model to use for generation.
     * Default: llama3
     */
    public string $defaultModel = 'llama3';

    /**
     * Request timeout in seconds.
     * Default: 120
     */
    public int $timeout = 300;

    /**
     * The model to use for embeddings.
     * Default: hf.co/nomic-ai/nomic-embed-text-v1.5-GGUF:Q8_0
     */
    public string $embeddingModel = 'hf.co/nomic-ai/nomic-embed-text-v1.5-GGUF:Q8_0';

    // --- Memory Logic Configuration ---
    public int $contextTokenBudget = 4000;
    public float $hybridSearchAlpha = 0.5;
    public float $decayScore = 0.05;
    public float $rewardScore = 0.5; // Boost rate
    public int $forcedRecentInteractions = 3;

    // --- NLP Configuration ---
    public array $nlpStopWords = [
        // Standard English Stop Words
        'a',
        'an',
        'and',
        'are',
        'as',
        'at',
        'be',
        'by',
        'for',
        'from',
        'has',
        'he',
        'in',
        'is',
        'it',
        'its',
        'of',
        'on',
        'that',
        'the',
        'to',
        'was',
        'were',
        'will',
        'with',
        'what',
        'when',
        'where',
        'who',
        'why',
        'how',
        'my',
        'we',
        'user',
        'note',
        'system',
        'please',

        // Common HTML Tags
        'a',
        'abbr',
        'address',
        'area',
        'article',
        'aside',
        'audio',
        'b',
        'base',
        'bdi',
        'bdo',
        'blockquote',
        'body',
        'br',
        'button',
        'canvas',
        'caption',
        'cite',
        'code',
        'col',
        'colgroup',
        'data',
        'datalist',
        'dd',
        'del',
        'details',
        'dfn',
        'dialog',
        'div',
        'dl',
        'dt',
        'em',
        'embed',
        'fieldset',
        'figcaption',
        'figure',
        'footer',
        'form',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'head',
        'header',
        'hr',
        'html',
        'i',
        'iframe',
        'img',
        'input',
        'ins',
        'kbd',
        'label',
        'legend',
        'li',
        'link',
        'main',
        'map',
        'mark',
        'meta',
        'meter',
        'nav',
        'noscript',
        'object',
        'ol',
        'optgroup',
        'option',
        'output',
        'p',
        'param',
        'picture',
        'pre',
        'progress',
        'q',
        'rp',
        'rt',
        'ruby',
        's',
        'samp',
        'script',
        'section',
        'select',
        'small',
        'source',
        'span',
        'strong',
        'style',
        'sub',
        'summary',
        'sup',
        'table',
        'tbody',
        'td',
        'template',
        'textarea',
        'tfoot',
        'th',
        'thead',
        'time',
        'tr',
        'track',
        'u',
        'ul',
        'var',
        'video',
        'wbr',
        'nbsp'
    ];
}
