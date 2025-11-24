<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use App\Modules\Gemini\Config\AGI;
use NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
use NlpTools\Stemmers\PorterStemmer;
use NlpTools\Utils\StopWords;

/**
 * Service for processing text into meaningful tokens (keywords).
 * This service encapsulates the NLP pipeline for tokenization, stop word removal,
 * and stemming to provide a consistent and reliable way of extracting keywords
 * for memory retrieval and analysis.
 */
class TokenService
{
    private array $stopWords;

    /**
     * Constructor.
     * Loads the NLP stop words from the custom AGI configuration.
     */
    public function __construct()
    {
        $this->stopWords = config(AGI::class)->nlpStopWords;
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
}
