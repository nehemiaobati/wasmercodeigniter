<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Entities;

use CodeIgniter\Entity\Entity;

/**
 * Represents a saved user prompt template.
 *
 * @property int|null $id
 * @property int|null $user_id
 * @property string|null $title
 * @property string|null $prompt_text
 * @property string|null $created_at
 * @property string|null $updated_at
 */
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
