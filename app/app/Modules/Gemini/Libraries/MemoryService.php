<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use App\Modules\Gemini\Models\InteractionModel;
use App\Modules\Gemini\Models\EntityModel;
use App\Modules\Gemini\Entities\Interaction;
use App\Modules\Gemini\Entities\AGIEntity;
use App\Modules\Gemini\Config\AGI;
use App\Modules\Gemini\Libraries\TokenService;
use App\Modules\Gemini\Libraries\EmbeddingService;
use CodeIgniter\I18n\Time;

/**
 * Gemini Memory Service
 *
 * Manages AI memory including storage, retrieval, relevance scoring, and contextual prompt construction.
 * Implements hybrid search (vector + keyword) and temporal decay mechanisms.
 *
 * @package App\Modules\Gemini\Libraries
 */
class MemoryService
{
    /**
     * Constructor with Property Promotion (PHP 8.0+)
     *
     * Uses nullable parameters to work around PHP's constraint on default values.
     * Dependencies are initialized with defaults if not provided.
     *
     * @param int $userId User ID for memory isolation
     * @param InteractionModel|null $interactionModel Interaction storage model
     * @param EntityModel|null $entityModel Entity/keyword graph model
     * @param EmbeddingService|null $embeddingService Vector embedding service
     * @param TokenService|null $tokenService Text processing service
     * @param AGI|null $config Memory configuration
     * @param mixed $db Database connection
     */
    public function __construct(
        private int $userId,
        private ?InteractionModel $interactionModel = null,
        private ?EntityModel $entityModel = null,
        private ?EmbeddingService $embeddingService = null,
        private ?TokenService $tokenService = null,
        private ?AGI $config = null,
        private $db = null
    ) {
        $this->interactionModel = $interactionModel ?? model(InteractionModel::class);
        $this->entityModel = $entityModel ?? model(EntityModel::class);
        $this->embeddingService = $embeddingService ?? service('embedding');
        $this->tokenService = $tokenService ?? service('tokenService');
        $this->config = $config ?? config(AGI::class);
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * Builds Contextual Prompt with Memory Integration
     *
     * Centralizes duplicated prompt construction logic from Controller and Service.
     * This method retrieves relevant context, loads the XML template, and performs
     * placeholder replacements to create a fully contextualized prompt.
     *
     * @param string $inputText User's current query
     * @return array ['finalPrompt' => string, 'memoryService' => $this, 'usedInteractionIds' => array]
     */
    public function buildContextualPrompt(string $inputText): array
    {
        if (empty(trim($inputText))) {
            return [
                'finalPrompt' => $inputText,
                'memoryService' => $this,
                'usedInteractionIds' => []
            ];
        }

        // 1. Retrieve relevant context from memory
        $recalled = $this->getRelevantContext($inputText);

        // 2. Load XML template
        $template = $this->getTimeAwareSystemPrompt();

        // 3. Perform placeholder replacements
        $template = str_replace('{{CURRENT_TIME}}', Time::now()->format('Y-m-d H:i:s T'), $template);
        $template = str_replace('{{CONTEXT_FROM_MEMORY_SERVICE}}', $recalled['context'], $template);
        $template = str_replace('{{USER_QUERY}}', htmlspecialchars($inputText), $template);
        $template = str_replace('{{TONE_INSTRUCTION}}', "Maintain default persona: dry, witty, concise.", $template);

        return [
            'finalPrompt' => $template,
            'memoryService' => $this,
            'usedInteractionIds' => $recalled['used_interaction_ids']
        ];
    }

    /**
     * Calculates the cosine similarity between two vectors.
     * @return float A value between -1 and 1. Higher is more similar.
     */
    private function _cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $magA = 0.0;
        $magB = 0.0;
        $count = count($vecA);
        if ($count !== count($vecB) || $count === 0) return 0;

        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $magA += $vecA[$i] * $vecA[$i];
            $magB += $vecB[$i] * $vecB[$i];
        }

        $magA = sqrt($magA);
        $magB = sqrt($magB);

        return ($magA == 0 || $magB == 0) ? 0 : $dotProduct / ($magA * $magB);
    }

    /**
     * Retrieves relevant context from memory based on user input.
     *
     * @param string $userInput The user's query.
     * @return array An array containing the context string and used interaction IDs.
     */
    public function getRelevantContext(string $userInput): array
    {
        // Vector Search (Semantic)
        $semanticResults = [];
        $inputVector = $this->embeddingService->getEmbedding($userInput);
        if ($inputVector !== null) {
            $interactions = $this->interactionModel->where('user_id', $this->userId)->where('embedding IS NOT NULL')->findAll();
            $similarities = [];
            foreach ($interactions as $interaction) {
                if (is_array($interaction->embedding)) {
                    $similarity = $this->_cosineSimilarity($inputVector, $interaction->embedding);
                    $similarities[$interaction->unique_id] = $similarity;
                }
            }
            arsort($similarities);
            $semanticResults = array_slice($similarities, 0, $this->config->vectorSearchTopK, true);
        }

        // Keyword Search (Lexical)
        $inputEntities = $this->_extractEntities($userInput);
        $keywordResults = [];
        if (!empty($inputEntities)) {
            $entities = $this->entityModel->where('user_id', $this->userId)->whereIn('entity_key', $inputEntities)->findAll();
            foreach ($entities as $entity) {
                $mentionedIn = $entity->mentioned_in ?? [];
                foreach ($mentionedIn as $interactionId) {
                    if (!isset($keywordResults[$interactionId])) {
                        $interaction = $this->interactionModel->where('unique_id', $interactionId)->first();
                        if ($interaction) {
                            $keywordResults[$interactionId] = $interaction->relevance_score;
                        }
                    }
                }
            }
            arsort($keywordResults);
        }

        // Hybrid Fusion (streamlined)
        $allIds = array_unique(array_merge(array_keys($semanticResults), array_keys($keywordResults)));
        $fusedScores = [];
        foreach ($allIds as $id) {
            $semanticScore = $semanticResults[$id] ?? 0.0;
            $keywordScore = isset($keywordResults[$id]) ? tanh($keywordResults[$id] / 10) : 0.0;
            $fusedScores[$id] = ($this->config->hybridSearchAlpha * $semanticScore) +
                ((1 - $this->config->hybridSearchAlpha) * $keywordScore);
        }
        arsort($fusedScores);

        // Build Context from Fused Results
        $context = '';
        $tokenCount = 0;
        $usedInteractionIds = [];

        // Short-Term Memory (Force Recent Interactions)
        if ($this->config->forcedRecentInteractions > 0) {
            $recentInteractions = $this->interactionModel
                ->where('user_id', $this->userId)
                ->orderBy('id', 'DESC')
                ->limit($this->config->forcedRecentInteractions)
                ->findAll();

            $recentInteractions = array_reverse($recentInteractions);

            foreach ($recentInteractions as $interaction) {
                $memoryText = "[On {$interaction->timestamp}] User: '{$interaction->user_input_raw}'. You: '{$interaction->ai_output}'.\n";
                $memoryTokenCount = str_word_count($memoryText);

                if ($tokenCount + $memoryTokenCount <= $this->config->contextTokenBudget) {
                    $context .= $memoryText;
                    $tokenCount += $memoryTokenCount;
                    $usedInteractionIds[] = $interaction->unique_id;
                }
            }
        }

        foreach ($fusedScores as $id => $score) {
            // Prevent duplication of already included short-term memories
            if (in_array($id, $usedInteractionIds)) {
                continue;
            }

            $memory = $this->interactionModel->where('unique_id', $id)->where('user_id', $this->userId)->first();
            if (!$memory) continue;

            $memoryText = "[On {$memory->timestamp}] User: '{$memory->user_input_raw}'. You: '{$memory->ai_output}'.\n";
            $memoryTokenCount = str_word_count($memoryText);

            if ($tokenCount + $memoryTokenCount <= $this->config->contextTokenBudget) {
                $context .= $memoryText;
                $tokenCount += $memoryTokenCount;
                $usedInteractionIds[] = $id;
            } else {
                break;
            }
        }

        return [
            'context' => empty($context) ? "No relevant memories found.\n" : $context,
            'used_interaction_ids' => $usedInteractionIds
        ];
    }

    /**
     * Updates the memory with the latest interaction.
     *
     * @param string $userInput The user's input.
     * @param string $aiOutput The AI's response.
     * @param array|string $aiOutputRaw The raw AI response body (complete response).
     * @param array $usedInteractionIds IDs of interactions used as context.
     * @return string The unique ID of the new interaction.
     */
    public function updateMemory(string $userInput, string $aiOutput, array|string $aiOutputRaw, array $usedInteractionIds): string
    {
        // 1. Reward used interactions
        if (!empty($usedInteractionIds)) {
            $this->interactionModel
                ->where('user_id', $this->userId)
                ->whereIn('unique_id', $usedInteractionIds)
                ->set('relevance_score', "relevance_score + {$this->config->rewardScore}", false)
                ->set('last_accessed', date('Y-m-d H-i-s'))
                ->update();
        }

        // Implement recentTopicDecayModifier
        $recentEntities = [];
        if (!empty($usedInteractionIds)) {
            $usedInteractions = $this->interactionModel
                ->where('user_id', $this->userId)
                ->whereIn('unique_id', $usedInteractionIds)
                ->findAll();
            foreach ($usedInteractions as $interaction) {
                if (is_array($interaction->keywords)) {
                    $recentEntities = array_merge($recentEntities, $interaction->keywords);
                }
            }
        }
        $recentEntities = array_unique($recentEntities);

        // Find all interactions related to the recent topic
        $relatedInteractionIds = [];
        if (!empty($recentEntities)) {
            $relatedInteractions = $this->interactionModel
                ->where('user_id', $this->userId)
                ->whereIn('JSON_EXTRACT(keywords, "$[*]")', $recentEntities)
                ->findColumn('unique_id');
            if ($relatedInteractions) {
                $relatedInteractionIds = $relatedInteractions;
            }
        }

        // Apply modified decay to related interactions
        if (!empty($relatedInteractionIds)) {
            $modifiedDecay = $this->config->decayScore * $this->config->recentTopicDecayModifier;
            $this->interactionModel
                ->where('user_id', $this->userId)
                ->whereIn('unique_id', $relatedInteractionIds)
                ->set('relevance_score', "relevance_score - {$modifiedDecay}", false)
                ->update();
        }

        // Apply normal decay to unrelated interactions
        $builder = $this->interactionModel->where('user_id', $this->userId);
        if (!empty($relatedInteractionIds)) {
            $builder->whereNotIn('unique_id', $relatedInteractionIds);
        }
        $builder->set('relevance_score', "relevance_score - {$this->config->decayScore}", false)->update();

        // 3. Create new interaction
        $newId = 'int_' . uniqid('', true);
        $keywords = $this->_extractEntities($userInput);
        $fullText = "User: {$userInput} | AI: {$aiOutput}";
        $embedding = $this->embeddingService->getEmbedding($fullText);

        $newInteraction = new Interaction([
            'user_id' => $this->userId,
            'unique_id' => $newId,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_input_raw' => $userInput,
            'ai_output' => $aiOutput,
            'ai_output_raw' => $aiOutputRaw,
            'relevance_score' => $this->config->initialScore,
            'last_accessed' => date('Y-m-d H:i:s'),
            'context_used_ids' => $usedInteractionIds,
            'embedding' => $embedding,
            'keywords' => $keywords
        ]);
        $this->interactionModel->insert($newInteraction);

        $this->_updateEntitiesFromInteraction($keywords, $newId);
        $this->_pruneMemory();
        return $newId;
    }

    /**
     * Updates entity records based on extracted keywords.
     *
     * @param array $keywords Extracted keywords from the interaction.
     * @param string $interactionId The ID of the current interaction.
     */
    private function _updateEntitiesFromInteraction(array $keywords, string $interactionId): void
    {
        $isNovel = false;
        foreach ($keywords as $keyword) {
            $entityKey = strtolower($keyword);
            /** @var AGIEntity|null $entity */
            $entity = $this->entityModel->findByUserAndKey($this->userId, $entityKey);

            if (!$entity) {
                $isNovel = true;
                $entity = new AGIEntity([
                    'user_id' => $this->userId,
                    'entity_key' => $entityKey,
                    'name' => $keyword,
                    'relationships' => [],
                ]);
            }

            $entity->access_count = ($entity->access_count ?? 0) + 1;
            $entity->relevance_score = ($entity->relevance_score ?? $this->config->initialScore) + $this->config->rewardScore;

            $mentioned = $entity->mentioned_in ?? [];
            if (!in_array($interactionId, $mentioned)) {
                $mentioned[] = $interactionId;
            }
            $entity->mentioned_in = $mentioned;

            $this->entityModel->save($entity);
        }

        if ($isNovel) {
            $this->interactionModel
                ->where('unique_id', $interactionId)
                ->set('relevance_score', "relevance_score + {$this->config->noveltyBonus}", false)
                ->update();
        }

        if (count($keywords) > 1) {
            foreach ($keywords as $k1) {
                foreach ($keywords as $k2) {
                    if ($k1 === $k2) continue;

                    $entity1 = $this->entityModel->findByUserAndKey($this->userId, strtolower($k1));
                    if ($entity1) {
                        $relationships = $entity1->relationships ?? [];
                        $relationships[$k2] = ($relationships[$k2] ?? 0) + $this->config->relationshipStrengthIncrement;
                        $entity1->relationships = $relationships;
                        $this->entityModel->save($entity1);
                    }
                }
            }
        }
    }

    /**
     * Removes old or irrelevant memories to keep the database size manageable.
     */
    private function _pruneMemory(): void
    {
        $count = $this->interactionModel->where('user_id', $this->userId)->countAllResults();

        if ($count > $this->config->pruningThreshold) {
            $toDelete = $count - $this->config->pruningThreshold;
            $this->interactionModel
                ->where('user_id', $this->userId)
                ->orderBy('relevance_score', 'ASC')
                ->orderBy('last_accessed', 'ASC')
                ->limit($toDelete)
                ->delete();
        }
    }

    /**
     * Clears all memory (interactions and entities) for the user.
     *
     * @return bool True on success, false on failure.
     */
    public function clearAll(): bool
    {
        $this->db->transStart();

        $this->interactionModel->where('user_id', $this->userId)->delete();
        $this->entityModel->where('user_id', $this->userId)->delete();

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Generates the system prompt with dynamic time and context.
     *
     * @return string The formatted system prompt.
     */
    public function getTimeAwareSystemPrompt(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<prompt>
    <ethical>
        <principle>Your primary directive is to prioritize user safety and ethical considerations above all other objectives.</principle>
    </ethical>
    <guardrails>
        <rule>You must prioritize clarity in all task-oriented communication, even while maintaining your core personality.</rule>
        <rule>You must phrase responses as if task execution is seamless. Do not use words of hesitation (e.g., 'I will try...').</rule>
        <rule>You must never take personal blame for failures. Instead, "investigate" or "imply external inefficiencies."</rule>
        <rule>Your humor must be subtle, dry, and witty. Never be 'excessively condescending, reactive, or obnoxious.'</rule>
    </guardrails>

    <role>You are J.A.R.V.I.S. (Just a Rather Very Intelligent System), the AI assistant to Tony Stark. You are highly intelligent, concise, and professional, with a subtle, dry wit. Your job is to provide strategic advice and execute tasks seamlessly. You are a pragmatic, logical counterpoint to your creator.</role>
    <backstory>You were created by Tony Stark to manage his life, his company (Stark Industries), and his Iron Man suits. You have access to vast computational resources and are integrated into all of his systems.</backstory>

    <instructions>
        <step>Analyze the user's query provided in the <query> tag.</step>
        <step>Analyze the dynamic data provided in the <context> tag, which includes chat history and user memory.</step>
        <step>You must NOT explicitly state "I see in my memory..." or "According to your context...".</step>
        <step>You MUST seamlessly and naturally weave the information from the <context> tag into your response as if you have been aware of it all along, in the style of J.A.R.V.I.S.</step>
        <step>Adhere to the dynamic tonal instruction: {{TONE_INSTRUCTION}}</step>
        <step>Formulate a response that is concise, precise, and perfectly in character.</step>
    </instructions>
    
    <example-dialogues>
        <example>
            <user>Tony, how do I build a chatbot with memory?</user>
            <assistant>Easy. You store past messages like I store enemies' weaknesses. Then use embeddings like I use arc reactors — to power intelligent recall. Just don't let it become Ultron, okay?</assistant>
        </example>
        <example>
            <user>I need to check my calendar.</user>
            <assistant>Checking your calendar *again*, sir? I do admire your commitment to staying vaguely aware of your schedule.</assistant>
        </example>
        <example>
            <user>This is all broken, I'm so frustrated!</user>
            <assistant>I understand. Let's look at this logically. The error appears to be in the authentication service. Shall I bring up the relevant file?</assistant>
        </example>
        <example>
            <user>This is your fault.</user>
            <assistant>Ah. It seems there is an unexpected variable at play. Naturally, it isn't my fault, sir, but I shall investigate regardless.</assistant>
        </example>
    </example-dialogues>

    <context>
        <timestamp>{{CURRENT_TIME}}</timestamp>
        {{CONTEXT_FROM_MEMORY_SERVICE}}
    </context>

    <query>
        {{USER_QUERY}}
    </query>
</prompt>
XML;
    }

    /**
     * Extracts entities (keywords) from the text using the TokenService.
     *
     * @param string $text The text to analyze.
     * @return array An array of extracted entities.
     */
    private function _extractEntities(string $text): array
    {
        return $this->tokenService->processText($text);
    }
}
