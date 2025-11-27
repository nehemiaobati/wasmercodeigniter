<?php

declare(strict_types=1);

namespace App\Modules\Ollama\Libraries;

/**
 * Simple keyword extractor for Ollama memory.
 * (A simplified version of the Gemini TokenService to keep dependencies low)
 */
class OllamaTokenService
{
    private array $stopWords = [
        'a',
        'an',
        'the',
        'and',
        'or',
        'but',
        'if',
        'then',
        'else',
        'when',
        'at',
        'by',
        'for',
        'from',
        'in',
        'out',
        'on',
        'to',
        'with',
        'is',
        'are',
        'was',
        'were',
        'be',
        'been',
        'being',
        'have',
        'has',
        'had',
        'do',
        'does',
        'did',
        'can',
        'could',
        'should',
        'would',
        'will',
        'user',
        'assistant',
        'system',
        'please',
        'note'
    ];

    public function processText(string $text): array
    {
        // 1. Strip HTML and lowercase
        $text = strtolower(strip_tags($text));

        // 2. Remove punctuation and special chars
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);

        // 3. Tokenize by whitespace
        $tokens = explode(' ', $text);

        // 4. Filter stop words and short words
        $keywords = array_filter($tokens, function ($word) {
            return strlen($word) > 2 && !in_array($word, $this->stopWords);
        });

        return array_unique(array_values($keywords));
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
