<?php declare(strict_types=1);

namespace App\Modules\Ollama\Models;

use CodeIgniter\Model;
use App\Modules\Ollama\Entities\OllamaInteraction;

class OllamaInteractionModel extends Model
{
    protected $table            = 'ollama_interactions';
    protected $primaryKey       = 'id';
    protected $returnType       = OllamaInteraction::class;
    protected $useTimestamps    = true;
    
    // Added missing fields from Migration
    protected $allowedFields    = [
        'user_id', 
        'prompt_hash', 
        'user_input', 
        'ai_response', 
        'ai_model', 
        'embedding',
        'relevance_score',
        'keywords'
    ];
}