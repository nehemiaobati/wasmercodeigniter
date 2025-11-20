<?php declare(strict_types=1);

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
    private const HYBRID_ALPHA = 0.5;
    private const DECAY_RATE   = 0.05;
    private const BOOST_RATE   = 0.5;

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
    public function processChat(string $prompt): array
    {
        // 1. Build Context
        $messages = $this->buildContext($prompt);

        // 2. Call API
        $result = $this->api->chat($messages);

        if (!$result['success']) {
            return $result;
        }

        // 3. Save Memory (Fire and forget logic handled internally)
        $this->saveInteraction($prompt, $result['response'], $result['model']);

        return $result;
    }

    private function buildContext(string $userInput): array
    {
        $inputVector = $this->api->embed($userInput);
        $keywords    = $this->tokenizer->processText($userInput);
        $relevantIds = $this->performHybridSearch($inputVector, $keywords);
        
        $systemPrompt = $this->constructSystemPrompt($relevantIds);
        
        return $this->assembleMessageChain($systemPrompt, $userInput);
    }

    private function constructSystemPrompt(array $memoryIds): string
    {
        $contextText = "";
        if (!empty($memoryIds)) {
            $memories = $this->interactionModel->whereIn('id', $memoryIds)->findAll();
            foreach ($memories as $mem) {
                $contextText .= "- [Memory]: User: '{$mem->user_input}' | AI: '{$mem->ai_response}'\n";
            }
        }

        return "You are DeepSeek R1. " .
               "Use <think></think> tags for reasoning on complex queries.\n\n" .
               "Relevant Context:\n" . ($contextText ?: "None available.");
    }

    private function assembleMessageChain(string $systemPrompt, string $userInput): array
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        // Add recent history (Last 3 interactions)
        $recent = $this->interactionModel
            ->where('user_id', $this->userId)
            ->orderBy('created_at', 'DESC')
            ->limit(3)
            ->findAll();
        
        foreach (array_reverse($recent) as $r) {
            $messages[] = ['role' => 'user', 'content' => $r->user_input];
            $messages[] = ['role' => 'assistant', 'content' => $r->ai_response];
        }

        $messages[] = ['role' => 'user', 'content' => $userInput];

        return $messages;
    }

    private function saveInteraction(string $input, string $response, string $modelName): void
    {
        $keywords  = $this->tokenizer->processText($input);
        $embedding = $this->api->embed("User: $input | AI: $response");

        // Create Entity object to utilize Casts (auto JSON encoding)
        $interaction = new OllamaInteraction([
            'user_id'         => $this->userId,
            'prompt_hash'     => hash('sha256', $input),
            'user_input'      => $input,
            'ai_response'     => $response,
            'ai_model'        => $modelName,
            'embedding'       => $embedding, // Entity cast handles array->json
            'keywords'        => $keywords,  // Entity cast handles array->json
            'relevance_score' => 1.0
        ]);

        $interactionId = $this->interactionModel->insert($interaction);

        if ($interactionId) {
            $this->updateKnowledgeGraph($keywords, (int)$interactionId);
            $this->applyDecay();
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
                // Entity casts 'mentioned_in' to array automatically
                $mentionedIn = $entity->mentioned_in ?? [];
                if (!in_array($interactionId, $mentionedIn)) {
                    $mentionedIn[] = $interactionId;
                }

                $entity->access_count++;
                $entity->relevance_score += self::BOOST_RATE;
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

    private function applyDecay(): void
    {
        // Direct Builder call to optimize bulk update
        $this->interactionModel->builder()
             ->where('user_id', $this->userId)
             ->set('relevance_score', "relevance_score - " . self::DECAY_RATE, false)
             ->update();
    }

    private function performHybridSearch(?array $vector, array $keywords): array
    {
        $scores = [];

        // 1. Vector Search
        if ($vector) {
            $candidates = $this->interactionModel
                ->where('user_id', $this->userId)
                ->where('embedding IS NOT NULL')
                ->orderBy('created_at', 'DESC')
                ->limit(50)
                ->findAll();

            foreach ($candidates as $c) {
                // Entity cast handles JSON decoding
                if (empty($c->embedding)) continue;
                
                $sim = $this->cosineSimilarity($vector, $c->embedding);
                $scores[$c->id] = ($scores[$c->id] ?? 0) + ($sim * self::HYBRID_ALPHA);
            }
        }

        // 2. Keyword Search
        if (!empty($keywords)) {
            $entities = $this->entityModel
                ->where('user_id', $this->userId)
                ->whereIn('entity_key', $keywords)
                ->findAll();

            foreach ($entities as $entity) {
                if (!empty($entity->mentioned_in)) {
                    foreach ($entity->mentioned_in as $intId) {
                        $scores[$intId] = ($scores[$intId] ?? 0) + ((1 - self::HYBRID_ALPHA) * ($entity->relevance_score / 10));
                    }
                }
            }
        }

        arsort($scores);
        return array_keys(array_slice($scores, 0, 5, true));
    }

    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dot = 0.0; $magA = 0.0; $magB = 0.0;
        foreach ($vecA as $i => $val) {
            if (!isset($vecB[$i])) continue;
            $dot += $val * $vecB[$i];
            $magA += $val * $val;
            $magB += $vecB[$i] * $vecB[$i];
        }
        return ($magA * $magB) == 0 ? 0.0 : $dot / (sqrt($magA) * sqrt($magB));
    }
}