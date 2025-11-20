<?php declare(strict_types=1);

namespace App\Modules\Ollama\Entities;

use CodeIgniter\Entity\Entity;

class OllamaInteraction extends Entity
{
    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'id'              => 'integer',
        'user_id'         => 'integer',
        'embedding'       => 'json-array', // Already existed
        'keywords'        => 'json-array', // <--- CRITICAL FIX
        'relevance_score' => 'float',
    ];
}