<?php declare(strict_types=1);

namespace App\Modules\Ollama\Models;

use CodeIgniter\Model;
use App\Modules\Ollama\Entities\OllamaEntity;

class OllamaEntityModel extends Model
{
    protected $table            = 'ollama_entities';
    protected $primaryKey       = 'id';
    protected $useTimestamps    = true;
    
    // FIX: Return Entity objects so ->property syntax works
    protected $returnType       = OllamaEntity::class;

    protected $allowedFields    = [
        'user_id', 
        'entity_key', 
        'name', 
        'access_count', 
        'relevance_score', 
        'mentioned_in'
    ];
}