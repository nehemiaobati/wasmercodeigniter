<?php declare(strict_types=1);

namespace App\Modules\Gemini\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int $id
 * @property int $user_id
 * @property string $unique_id
 * @property string $timestamp
 * @property string $user_input_raw
 * @property string $ai_output
 * @property float $relevance_score
 * @property string $last_accessed
 * @property string|null $context_used_ids
 * @property string|null $embedding
 * @property string|null $keywords
 */
class Interaction extends Entity
{
    protected $dates   = ['timestamp', 'last_accessed', 'created_at', 'updated_at'];
    protected $casts   = [
        'id' => 'integer',
        'user_id' => 'integer',
        'relevance_score' => 'float',
        'context_used_ids' => 'json-array',
        'embedding' => 'json-array',
        'keywords' => 'json-array',
    ];
}
