<?php

declare(strict_types=1);

namespace App\Modules\Ollama\Libraries;

use App\Modules\Ollama\Config\Ollama;
use App\Modules\Ollama\Entities\OllamaEntity;
use App\Modules\Ollama\Entities\OllamaInteraction;
use App\Modules\Ollama\Models\OllamaInteractionModel;
use App\Modules\Ollama\Models\OllamaEntityModel;

class OllamaMemoryService
{
    private Ollama $config;
    private OllamaInteractionModel $interactionModel;
    private OllamaEntityModel $entityModel;
    private OllamaService $api;
    private OllamaTokenService $tokenizer;
    private int $userId;

    // Tuning Parameters
    // Tuning Parameters


    public function __construct(int $userId)
    {
        $this->userId           = $userId;
        $this->config           = config(Ollama::class);
        $this->interactionModel = new OllamaInteractionModel();
        $this->entityModel      = new OllamaEntityModel();
        $this->api              = new OllamaService();
        $this->tokenizer        = new OllamaTokenService();
    }

    /**
     * Main orchestration method for handling a user chat interaction.
     */
    public function processChat(string $prompt, ?string $model = null): array
    {
        // 1. Build Context (Gemini Style)
        $contextData = $this->getRelevantContext($prompt);

        // 2. Construct System Prompt
        $systemPrompt = $this->constructSystemPrompt($contextData['context']);

        // 3. Assemble Messages (System + User only, as history is in context)
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt]
        ];

        // 4. Call API
        $result = $this->api->chat($messages, $model);

        if (!$result['success']) {
            return $result;
        }

        // 5. Save Memory
        $this->saveInteraction($prompt, $result['response'], $result['model'], $contextData['used_interaction_ids']);

        return $result;
    }

    /**
     * Retrieves relevant context from memory based on user input.
     * Replicates Gemini's MemoryService workflow.
     */
    private function getRelevantContext(string $userInput): array
    {
        // 1. Vector Search (Semantic)
        $semanticResults = [];
        $inputVector = $this->api->embed($userInput);
        if (!empty($inputVector)) {
            $candidates = $this->interactionModel
                ->where('user_id', $this->userId)
                ->where('embedding IS NOT NULL')
                ->findAll();

            foreach ($candidates as $c) {
                if (empty($c->embedding)) continue;
                $sim = $this->cosineSimilarity($inputVector, $c->embedding);
                $semanticResults[$c->id] = $sim;
            }
            arsort($semanticResults);
            $semanticResults = array_slice($semanticResults, 0, 50, true);
        }

        // 2. Keyword Search (Lexical)
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
                    foreach ($entity->mentioned_in as $intId) {
                        $candidateIds[] = $intId;
                    }
                }
            }

            if (!empty($candidateIds)) {
                $candidateIds = array_unique($candidateIds);
                $interactions = $this->interactionModel
                    ->where('user_id', $this->userId)
                    ->whereIn('id', $candidateIds)
                    ->findAll();

                foreach ($interactions as $int) {
                    // Gemini uses the interaction's persistent relevance_score
                    $keywordResults[$int->id] = $int->relevance_score;
                }
            }
            arsort($keywordResults);
        }

        // 3. Hybrid Fusion
        $fusedScores = [];
        $allIds = array_unique(array_merge(array_keys($semanticResults), array_keys($keywordResults)));
        foreach ($allIds as $id) {
            $semanticScore = $semanticResults[$id] ?? 0.0;
            // Apply tanh normalization with scaling (Gemini uses / 10)
            $keywordScore  = isset($keywordResults[$id]) ? tanh($keywordResults[$id] / 10) : 0.0;
            $fusedScores[$id] = ($this->config->hybridSearchAlpha * $semanticScore) + ((1 - $this->config->hybridSearchAlpha) * $keywordScore);
        }
        arsort($fusedScores);

        // 4. Build Context String
        $context = "";
        $tokenCount = 0;
        $usedInteractionIds = [];

        // A. Forced Recent Interactions (Short-Term Memory)
        $recentInteractions = [];
        if ($this->config->forcedRecentInteractions > 0) {
            $recentInteractions = $this->interactionModel
                ->where('user_id', $this->userId)
                ->orderBy('created_at', 'DESC')
                ->limit($this->config->forcedRecentInteractions)
                ->findAll();
        }

        // Reverse to maintain chronological order
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

        // B. Relevant Long-Term Memories
        foreach ($fusedScores as $id => $score) {
            // Skip if already included via recent list
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
                break; // Stop if budget exceeded
            }
        }

        return [
            'context' => empty($context) ? "No previous context available." : $context,
            'used_interaction_ids' => $usedInteractionIds
        ];
    }

    private function constructSystemPrompt(string $contextText): string
    {
        return "You are DeepSeek R1, a helpful AI assistant. " .
            "CONTEXT FROM MEMORY:\n" . $contextText . "\n\n" .
            "INSTRUCTIONS:\n" .
            "1. Use the above context to answer the user's query.\n" .
            "2. If the context contains the answer, cite it implicitly.\n" .
            "3. Do not explicitly say 'According to my memory'.";
    }

    private function saveInteraction(string $input, string $response, string $modelName, array $usedIds): void
    {
        $keywords  = $this->tokenizer->processText($input);

        // Strip HTML tags for cleaner embedding
        $cleanInput = strip_tags($input);
        $cleanResponse = strip_tags($response);
        $embedding = $this->api->embed("User: $cleanInput | AI: $cleanResponse");

        if (empty($embedding)) {
            log_message('error', 'Ollama Memory: Embedding generation failed for interaction.');
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
            $this->updateKnowledgeGraph($keywords, (int)$interactionId);
            $this->applyDecay($usedIds); // Reward used, decay others
        } else {
            log_message('error', 'Ollama Memory: Failed to save interaction. Errors: ' . json_encode($this->interactionModel->errors()));
        }
    }

    private function updateKnowledgeGraph(array $keywords, int $interactionId): void
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

    private function applyDecay(array $usedIds): void
    {
        // 1. Reward Used Interactions
        if (!empty($usedIds)) {
            $this->interactionModel->builder()
                ->where('user_id', $this->userId)
                ->whereIn('id', $usedIds) // Note: 'id' not 'unique_id' for Ollama model
                ->set('relevance_score', "relevance_score + " . $this->config->rewardScore, false)
                ->update();
        }

        // 2. Decay All (Simplification: Decay everyone, the boost above offsets it for used ones)
        // Or strictly: Decay unused. Let's decay all to keep scores normalized over time.
        $this->interactionModel->builder()
            ->where('user_id', $this->userId)
            ->set('relevance_score', "relevance_score - " . $this->config->decayScore, false)
            ->update();
    }

    private function cosineSimilarity(array $vecA, array $vecB): float
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
