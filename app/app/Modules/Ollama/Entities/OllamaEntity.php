<?php

declare(strict_types=1);

namespace App\Modules\Ollama\Entities;

class OllamaEntity extends \CodeIgniter\Entity\Entity
{
    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'id'              => 'integer',
        'user_id'         => 'integer',
        'access_count'    => 'integer',
        'relevance_score' => 'float',
        // Crucial: Automatically converts the Database JSON string to a PHP Array and vice-versa
        'mentioned_in'    => 'json-array',
    ];
}
