<?php

declare(strict_types=1);

namespace App\Modules\Ollama\Libraries;

use App\Modules\Ollama\Config\Ollama;
use App\Modules\Ollama\Entities\OllamaEntity;
use App\Modules\Ollama\Entities\OllamaInteraction;
use App\Modules\Ollama\Models\OllamaInteractionModel;
use App\Modules\Ollama\Models\OllamaEntityModel;

/**
 * Ollama Memory Service
 *
 * Implements a sophisticated Hybrid Memory System for conversational AI context.
 * Combines vector embeddings (semantic search) with keyword extraction (lexical search)
 * to retrieve the most relevant historical interactions for each query.
 *
 * Key Components:
 * - Vector Search: Uses cosine similarity on embeddings for semantic relevance
 * - Keyword Search: Tracks entity mentions and relevance scores for lexical matching
 * - Hybrid Fusion: Weighted combination (configurable alpha) of both search strategies
 * - Temporal Decay: Rewards recently used memories, gradually decays unused ones
 * - Short-Term Memory: Forces inclusion of N most recent interactions
 *
 * @package App\Modules\Ollama\Libraries
 */
class OllamaMemoryService
{
    /**
     * Constructor with Property Promotion (PHP 8.0+)
     *
     * Implements the same nullable parameter pattern as OllamaService due to PHP's
     * constraint on default values (cannot use function calls). All dependencies are
     * injected via constructor for testability and flexibility.
     *
     * @param int $userId User ID for memory isolation (each user has separate memory space)
     * @param Ollama|null $config Configuration object for memory tuning parameters
     * @param OllamaInteractionModel|null $interactionModel Manages chat history storage
     * @param OllamaEntityModel|null $entityModel Manages keyword/entity graph
     * @param OllamaService|null $api Service for embeddings and chat completions
     * @param OllamaTokenService|null $tokenizer Service for text processing and keyword extraction
     */
    public function __construct(
        private int $userId,
        private ?Ollama $config = null,
        private ?OllamaInteractionModel $interactionModel = null,
        private ?OllamaEntityModel $entityModel = null,
        private ?OllamaService $api = null,
        private ?OllamaTokenService $tokenizer = null
    ) {
        // Initialize all dependencies with defaults if not injected
        $this->config = $config ?? config(Ollama::class);
        $this->interactionModel = $interactionModel ?? new OllamaInteractionModel();
        $this->entityModel = $entityModel ?? new OllamaEntityModel();
        $this->api = $api ?? new OllamaService();
        $this->tokenizer = $tokenizer ?? new OllamaTokenService();
    }

    public function processChat(string $prompt, ?string $model = null, array $images = []): array
    {
        $contextData = $this->_getRelevantContext($prompt);
        $systemPrompt = $this->_constructSystemPrompt($contextData['context']);

        $userMessage = ['role' => 'user', 'content' => $prompt];
        if (!empty($images)) {
            $userMessage['images'] = $images;
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            $userMessage
        ];

        $result = $this->api->chat($messages, $model);

        // Handle new standardized return format
        if (isset($result['status']) && $result['status'] === 'error') {
            return [
                'error' => $result['message'] ?? 'Unknown error',
                'success' => false
            ];
        }

        // Legacy format support
        if (isset($result['success']) && !$result['success']) {
            return $result;
        }

        // Extract data from new format or legacy format
        $aiResponse = $result['data']['response'] ?? $result['response'] ?? '';
        $usedModel = $result['data']['model'] ?? $result['model'] ?? $model ?? 'unknown';

        $this->_saveInteraction($prompt, $aiResponse, $usedModel, $contextData['used_interaction_ids']);

        // Return in legacy format for backward compatibility with controller
        return [
            'success' => true,
            'response' => $aiResponse,
            'model' => $usedModel,
            'usage' => $result['data']['usage'] ?? $result['usage'] ?? []
        ];
    }

    private function _getRelevantContext(string $userInput): array
    {
        $semanticResults = [];
        $embedResponse = $this->api->embed($userInput);
        $inputVector = ($embedResponse['status'] === 'success') ? $embedResponse['data'] : [];

        if (!empty($inputVector)) {
            $candidates = $this->interactionModel
                ->where('user_id', $this->userId)
                ->where('embedding IS NOT NULL')
                ->findAll();

            foreach ($candidates as $c) {
                if (empty($c->embedding)) continue;
                $similarity = $this->_cosineSimilarity($inputVector, $c->embedding);
                $semanticResults[$c->id] = $similarity;
            }
            arsort($semanticResults);
            $semanticResults = array_slice($semanticResults, 0, 50, true);
        }

        $keywords = $this->tokenizer->processText($userInput);
        $keywordResults = [];

        if (!empty($keywords)) {
            $entities = $this->entityModel
                ->where('user_id', $this->userId)
                ->whereIn('entity_key', $keywords)
                ->findAll();

            $candidateIds = [];
            foreach ($entities as $entity) {
                if (!empty($entity->mentioned_in)) {
                    $candidateIds = array_merge($candidateIds, $entity->mentioned_in);
                }
            }

            if (!empty($candidateIds)) {
                $candidateIds = array_unique($candidateIds);
                $interactions = $this->interactionModel
                    ->where('user_id', $this->userId)
                    ->whereIn('id', $candidateIds)
                    ->findAll();

                foreach ($interactions as $int) {
                    $keywordResults[$int->id] = $int->relevance_score;
                }
            }
            arsort($keywordResults);
        }

        $fusedScores = [];
        $allIds = array_unique(array_merge(array_keys($semanticResults), array_keys($keywordResults)));

        foreach ($allIds as $id) {
            $semanticScore = $semanticResults[$id] ?? 0.0;
            $keywordScore  = isset($keywordResults[$id]) ? tanh($keywordResults[$id] / 10) : 0.0;
            $fusedScores[$id] = ($this->config->hybridSearchAlpha * $semanticScore) + ((1 - $this->config->hybridSearchAlpha) * $keywordScore);
        }
        arsort($fusedScores);

        $context = "";
        $tokenCount = 0;
        $usedInteractionIds = [];

        $recentInteractions = [];
        if ($this->config->forcedRecentInteractions > 0) {
            $recentInteractions = $this->interactionModel
                ->where('user_id', $this->userId)
                ->orderBy('created_at', 'DESC')
                ->limit($this->config->forcedRecentInteractions)
                ->findAll();
        }

        $recentInteractions = array_reverse($recentInteractions);

        foreach ($recentInteractions as $interaction) {
            $memoryText = "[Recent]: User: '{$interaction->user_input}' | AI: '{$interaction->ai_response}'\n";
            $itemTokens = $this->tokenizer->estimateTokenCount($memoryText);

            if ($tokenCount + $itemTokens <= $this->config->contextTokenBudget) {
                $context .= $memoryText;
                $tokenCount += $itemTokens;
                $usedInteractionIds[] = $interaction->id;
            }
        }

        foreach ($fusedScores as $id => $score) {
            if (in_array($id, $usedInteractionIds)) {
                continue;
            }

            $memory = $this->interactionModel->find($id);
            if (!$memory) continue;

            $memoryText = "[Relevant]: User: '{$memory->user_input}' | AI: '{$memory->ai_response}'\n";
            $itemTokens = $this->tokenizer->estimateTokenCount($memoryText);

            if ($tokenCount + $itemTokens <= $this->config->contextTokenBudget) {
                $context .= $memoryText;
                $tokenCount += $itemTokens;
                $usedInteractionIds[] = $id;
            } else {
                break;
            }
        }

        return [
            'context' => empty($context) ? "No previous context available." : $context,
            'used_interaction_ids' => $usedInteractionIds
        ];
    }

    private function _constructSystemPrompt(string $contextText): string
    {
        return "You are a helpful AI assistant. " .
            "CONTEXT FROM MEMORY:\n" . $contextText . "\n\n" .
            "INSTRUCTIONS:\n" .
            "1. Use the above context to answer the user's query.\n" .
            "2. If the context contains the answer, cite it implicitly.\n" .
            "3. Do not explicitly say 'According to my memory'.";
    }

    private function _saveInteraction(string $input, string $response, string $modelName, array $usedIds): void
    {
        $keywords  = $this->tokenizer->processText($input);

        $cleanInput = strip_tags($input);
        $cleanResponse = strip_tags($response);
        $embedResponse = $this->api->embed("User: $cleanInput | AI: $cleanResponse");
        $embedding = ($embedResponse['status'] === 'success') ? $embedResponse['data'] : [];

        if (empty($embedding)) {
            log_message('error', 'Ollama Memory: Embedding generation failed for interaction.', [
                'error' => $embedResponse['message'] ?? 'Unknown error'
            ]);
        } else {
            log_message('info', 'Ollama Memory: Embedding generated. Size: ' . count($embedding));
        }

        $interaction = new OllamaInteraction([
            'user_id'         => $this->userId,
            'prompt_hash'     => hash('sha256', $input),
            'user_input'      => $input,
            'ai_response'     => $response,
            'ai_model'        => $modelName,
            'embedding'       => $embedding,
            'keywords'        => $keywords,
            'relevance_score' => 1.0
        ]);

        $interactionId = $this->interactionModel->insert($interaction);

        if ($interactionId) {
            log_message('info', 'Ollama Memory: Interaction saved. ID: ' . $interactionId);
            $this->_updateKnowledgeGraph($keywords, (int)$interactionId);
            $this->_applyDecay($usedIds);
        } else {
            log_message('error', 'Ollama Memory: Failed to save interaction. Errors: ' . json_encode($this->interactionModel->errors()));
        }
    }

    private function _updateKnowledgeGraph(array $keywords, int $interactionId): void
    {
        foreach ($keywords as $word) {
            $entity = $this->entityModel
                ->where('user_id', $this->userId)
                ->where('entity_key', $word)
                ->first();

            if ($entity) {
                $mentionedIn = $entity->mentioned_in ?? [];
                if (!in_array($interactionId, $mentionedIn)) {
                    $mentionedIn[] = $interactionId;
                }

                $entity->access_count++;
                $entity->relevance_score += $this->config->rewardScore;
                $entity->mentioned_in     = $mentionedIn;

                $this->entityModel->save($entity);
            } else {
                $newEntity = new OllamaEntity([
                    'user_id'         => $this->userId,
                    'entity_key'      => $word,
                    'name'            => ucfirst($word),
                    'access_count'    => 1,
                    'relevance_score' => 1.0,
                    'mentioned_in'    => [$interactionId]
                ]);
                $this->entityModel->insert($newEntity);
            }
        }
    }

    private function _applyDecay(array $usedIds): void
    {
        if (!empty($usedIds)) {
            $this->interactionModel->builder()
                ->where('user_id', $this->userId)
                ->whereIn('id', $usedIds)
                ->set('relevance_score', "relevance_score + " . $this->config->rewardScore, false)
                ->update();
        }

        $this->interactionModel->builder()
            ->where('user_id', $this->userId)
            ->set('relevance_score', "relevance_score - " . $this->config->decayScore, false)
            ->update();
    }

    private function _cosineSimilarity(array $vecA, array $vecB): float
    {
        $dot = 0.0;
        $magA = 0.0;
        $magB = 0.0;

        foreach ($vecA as $i => $val) {
            if (!isset($vecB[$i])) continue;
            $dot += $val * $vecB[$i];
            $magA += $val * $val;
            $magB += $vecB[$i] * $vecB[$i];
        }

        return ($magA * $magB) == 0 ? 0.0 : $dot / (sqrt($magA) * sqrt($magB));
    }
}
