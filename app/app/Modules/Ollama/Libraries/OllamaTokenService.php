<?php

declare(strict_types=1);

namespace App\Modules\Ollama\Libraries;

use NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
use NlpTools\Stemmers\PorterStemmer;
use NlpTools\Utils\StopWords;

/**
 * Simple keyword extractor for Ollama memory.
 * (A simplified version of the Gemini TokenService to keep dependencies low)
 */
class OllamaTokenService
{
    private array $stopWords;

    /**
     * Constructor.
     * Loads the NLP stop words from the custom Ollama configuration.
     */
    public function __construct()
    {
        $this->stopWords = config(\App\Modules\Ollama\Config\Ollama::class)->nlpStopWords;
    }

    /**
     * Processes raw text through an NLP pipeline to extract a clean list of keyword stems.
     *
     * @param string $text The input text to process.
     * @return array<string> A unique, filtered, and stemmed list of keywords.
     */
    public function processText(string $text): array
    {
        // 1. **[NEW FIX]** Strip all HTML tags from the input string first.
        $text = strip_tags($text);

        // 2. Sanitize and Normalize Text
        $text = strtolower($text);
        $text = preg_replace('/https?:\/\/[^\s]+/', ' ', $text); // Remove URLs

        // 3. Tokenize the text using the library's robust tokenizer
        $tokenizer = new WhitespaceAndPunctuationTokenizer();
        $tokens = $tokenizer->tokenize($text);

        // 4. Filter out stop words using the configured list
        $stopWordsFilter = new StopWords($this->stopWords);
        $filteredTokens = [];
        foreach ($tokens as $token) {
            // The transform method returns null if the token is a stop word
            $transformedToken = $stopWordsFilter->transform($token);
            if ($transformedToken !== null) {
                $filteredTokens[] = $transformedToken;
            }
        }

        // 5. Reduce words to their root form (stemming)
        $stemmer = new PorterStemmer();
        $stemmedTokens = array_map([$stemmer, 'stem'], $filteredTokens);

        // 6. Final cleanup: return unique, non-empty tokens with a length > 2
        return array_values(array_filter(array_unique($stemmedTokens), fn($word) => strlen($word) > 2));
    }

    /**
     * Estimates token count for a given text.
     * Uses a rough approximation of 4 characters per token.
     */
    public function estimateTokenCount(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}
