<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuration for the AGI (Artificial General Intelligence) system.
 *
 * This class holds all the configuration settings related to the AGI's
 * behavior, including API endpoints, model IDs, memory management,
 * and search tuning parameters.
 */
class AGI extends BaseConfig
{
    // --- API Configuration ---
    // Note: API Key and Endpoint are typically defined as constants or environment variables,
    // but for demonstration, they are commented out here as they were in the original prompt.
    // If these were to be used, they would likely be defined in a separate config file or .env.
    // define('GEMINI_API_KEY', 'AIzaSyBEkzJRNr-CvwqVCJQtcYs3bb2M-Ikq0pA');
    // define('MODEL_ID', 'gemini-2.5-flash-lite');
    // define('API_ENDPOINT', 'generateContent');

    // --- Embedding Configuration ---

    /**
     * Master switch to enable or disable the entire vector/semantic search system.
     * Set to 'false' to revert to a purely keyword-based search.
     *
     * @var bool
     */
    public bool $enableEmbeddings = true;

    /**
     * The specialized model used for converting text into numerical vectors (embeddings).
     * 'text-embedding-004' is optimized for this task and is highly efficient.
     *
     * @var string
     */
    public string $embeddingModel = 'text-embedding-004';


    // --- File Paths ---
    // Note: File paths are typically defined as constants or environment variables.
    // These are commented out here as they were in the original prompt.
    // If these were to be used, they would likely be defined in a separate config file or .env.
    // define('DATA_DIR', __DIR__ . '/data');
    // define('INTERACTIONS_FILE', DATA_DIR . '/interactions.json');
    // define('ENTITIES_FILE', DATA_DIR . '/entities.json');
    // define('PROMPTS_LOG_FILE', DATA_DIR . '/prompts.json');


    // --- Memory Logic Configuration ---

    /**
     * The amount to increase a memory's 'relevance_score' when it is successfully
     * used as context for a good response. Higher values make the AI "learn" faster.
     *
     * @var float
     */
    public float $rewardScore = 0.5;

    /**
     * The base amount to decrease a memory's 'relevance_score' during each new
     * interaction. This simulates "forgetting" and prevents old, unused memories from cluttering the system.
     *
     * @var float
     */
    public float $decayScore = 0.05;

    /**
     * The starting 'relevance_score' for any new memory created.
     *
     * @var float
     */
    public float $initialScore = 1.0;

    /**
     * The maximum number of interactions to keep in memory. When this number is
     * exceeded, the system will delete the interactions with the lowest relevance scores.
     *
     * @var int
     */
    public int $pruningThreshold = 500;

    /**
     * The maximum number of tokens to include in the context sent to the AI.
     * This prevents the prompt from becoming too large and expensive.
     *
     * @var int
     */
    public int $contextTokenBudget = 4000;

    /**
     * --- NEW: Short-Term Memory Configuration ---
     * The number of most recent interactions to ALWAYS include in the context.
     * Set to 1 to guarantee the AI remembers the very last thing said.
     * Set to 2 or 3 to give it a slightly better conversational short-term memory.
     * Set to 0 to disable and rely purely on the hybrid search.
     *
     * @var int
     */
    public int $forcedRecentInteractions = 3;


    // --- Hybrid Search Tuning ---

    /**
     * The core dial that balances keyword search against vector search.
     * 0.0 = 100% keyword-based. The AI will only find exact matches.
     * 1.0 = 100% semantic-based. The AI will only find conceptually similar ideas.
     * 0.5 = A balanced mix, providing both precision and conceptual relevance.
     *
     * @var float
     */
    public float $hybridSearchAlpha = 0.5;

    /**
     * The number of top results to fetch from the semantic (vector) search stage.
     * A higher number allows the fusion algorithm to consider more conceptually related
     * memories, but may slightly slow down the retrieval process.
     *
     * @var int
     */
    public int $vectorSearchTopK = 15;


    // --- Advanced Scoring & Relationships ---

    /**
     * An extra 'relevance_score' bonus given to a new memory if it contains a
     * keyword (entity) the AI has never seen before. Encourages learning new topics.
     *
     * @var float
     */
    public float $noveltyBonus = 0.3;

    /**
     * The amount to increase the strength of the connection between two keywords
     * every time they appear in the same user prompt.
     *
     * @var float
     */
    public float $relationshipStrengthIncrement = 0.1;

    /**
     * A multiplier that reduces the 'DECAY_SCORE' for memories related to the
     * current conversation topic. This helps the AI "stay on topic" by forgetting
     * relevant memories more slowly.
     *
     * @var float
     */
    public float $recentTopicDecayModifier = 0.1;

    /**
     * The amount to increase the 'relevance_score' of an interaction and its
     * context when the user clicks the "Good" feedback button.
     *
     * @var float
     */
    public float $userFeedbackReward = 0.5;

    /**
     * The amount to decrease the 'relevance_score' of an interaction and its
     * context when the user clicks the "Bad" feedback button.
     *
     * @var float
     */
    public float $userFeedbackPenalty = -0.5;
}
