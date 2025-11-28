<?php

namespace App\Modules\Ollama\Entities;

use CodeIgniter\Entity\Entity;

class OllamaPrompt extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'      => 'integer',
        'user_id' => 'integer',
    ];
}
