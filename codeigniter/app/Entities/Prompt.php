<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Prompt extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'          => 'integer',
        'user_id'     => 'integer',
        'title'       => 'string',
        'prompt_text' => 'string',
    ];
}
