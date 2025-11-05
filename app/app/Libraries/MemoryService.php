<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\InteractionModel;
use App\Models\EntityModel;
use App\Entities\Interaction;
use App\Entities\AGIEntity;
use Config\Custom\AGI;

/**
 * Manages the AI's memory, including storage, retrieval, and relevance scoring.
 */
class MemoryService
{
    private int $userId;
    private InteractionModel $interactionModel;
    private EntityModel $entityModel;
    private EmbeddingService $embeddingService;
    private TokenService $tokenService;
    private AGI $config;

    /**
     * Constructor.
     * @param int $userId The ID of the current user to scope the memory.
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->interactionModel = model(InteractionModel::class);
        $this->entityModel = model(EntityModel::class);
        $this->embeddingService = service('embedding');
        $this->tokenService = service('tokenService');
        $this->config = config(AGI::class);
    }

    /**
     * Calculates the cosine similarity between two vectors.
     * @return float A value between -1 and 1. Higher is more similar.
     */
    private function cosineSimilarity(array $vecA, array $vecB): float
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
                    $similarity = $this->cosineSimilarity($inputVector, $interaction->embedding);
                    $similarities[$interaction->unique_id] = $similarity;
                }
            }
            arsort($similarities);
            $semanticResults = array_slice($similarities, 0, $this->config->vectorSearchTopK, true);
        }

        // Keyword Search (Lexical)
        $inputEntities = $this->extractEntities($userInput);
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

        // Hybrid Fusion
        $fusedScores = [];
        $allIds = array_unique(array_merge(array_keys($semanticResults), array_keys($keywordResults)));
        foreach ($allIds as $id) {
            $semanticScore = $semanticResults[$id] ?? 0.0;
            $relevanceScore = isset($keywordResults[$id]) ? tanh($keywordResults[$id] / 10) : 0.0;
            $fusedScores[$id] = ($this->config->hybridSearchAlpha * $semanticScore) + ((1 - $this->config->hybridSearchAlpha) * $relevanceScore);
        }
        arsort($fusedScores);

        // Build Context from Fused Results
        $context = '';
        $tokenCount = 0;
        $usedInteractionIds = [];
        foreach ($fusedScores as $id => $score) {
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

    public function updateMemory(string $userInput, string $aiOutput, array $usedInteractionIds): string
    {
        // 1. Reward used interactions
        if (!empty($usedInteractionIds)) {
            $this->interactionModel
                ->where('user_id', $this->userId)
                ->whereIn('unique_id', $usedInteractionIds)
                ->set('relevance_score', "relevance_score + {$this->config->rewardScore}", false)
                ->set('last_accessed', date('Y-m-d H:i:s'))
                ->update();
        }

        // 2. Decay all interactions
        $this->interactionModel
            ->where('user_id', $this->userId)
            ->set('relevance_score', "relevance_score - {$this->config->decayScore}", false)
            ->update();

        // 3. Create new interaction
        $newId = 'int_' . uniqid('', true);
        $keywords = $this->extractEntities($userInput);
        $fullText = "User: {$userInput} | AI: {$aiOutput}";
        $embedding = $this->embeddingService->getEmbedding($fullText);

        $newInteraction = new Interaction([
            'user_id' => $this->userId,
            'unique_id' => $newId,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_input_raw' => $userInput,
            'ai_output' => $aiOutput,
            'relevance_score' => $this->config->initialScore,
            'last_accessed' => date('Y-m-d H:i:s'),
            'context_used_ids' => $usedInteractionIds,
            'embedding' => $embedding,
            'keywords' => $keywords
        ]);
        $this->interactionModel->insert($newInteraction);

        $this->updateEntitiesFromInteraction($keywords, $newId);
        $this->pruneMemory();
        return $newId;
    }

    private function updateEntitiesFromInteraction(array $keywords, string $interactionId): void
    {
        foreach ($keywords as $keyword) {
            $entityKey = strtolower($keyword);
            /** @var AGIEntity|null $entity */
            $entity = $this->entityModel->findByUserAndKey($this->userId, $entityKey);

            if (!$entity) {
                $entity = new AGIEntity([
                    'user_id' => $this->userId,
                    'entity_key' => $entityKey,
                    'name' => $keyword,
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
    }

    private function pruneMemory(): void
    {
        $count = $this->interactionModel->where('user_id', $this->userId)->countAllResults();
        if ($count > $this->config->pruningThreshold) {
            $limit = $count - $this->config->pruningThreshold;
            $interactionsToDelete = $this->interactionModel
                ->where('user_id', $this->userId)
                ->orderBy('relevance_score', 'ASC')
                ->limit($limit)
                ->findColumn('id');

            if (!empty($interactionsToDelete)) {
                $this->interactionModel->delete($interactionsToDelete);
            }
        }
    }

    public function getTimeAwareSystemPrompt(): string
    {
        return "**PRIMARY DIRECTIVE: YOU ARE A HELPFUL, TIME-AWARE ASSISTANT.**\n\n" .
            "**RULES OF OPERATION:**\n" .
            "1.  **ANALYZE TIMESTAMPS:** You will be given a `CURRENT_TIME` and `RECALLED_CONTEXT`. Use this to understand the history of events.\n" .
            "2.  **CALCULATE RELATIVE TIME:** Interpret expressions like 'yesterday' against the provided `CURRENT_TIME`.\n\n" .
            "**TOOL EXECUTION MANDATE:**\n" .
            "3.  **DIRECTLY USE TOOLS:** You have a `googleSearch` tool. Use it to directly answer the user's question. **DO NOT describe that you are going to use a tool.** Execute it and provide the final answer based on its output.\n" .
            "4.  **FULFILL THE REQUEST:** If the user provides a URL, use your search ability to access its content and provide a summary. If they ask a general question, use search to find the answer.";
    }

    private function extractEntities(string $text): array
    {
        return $this->tokenService->processText($text);
    }
}