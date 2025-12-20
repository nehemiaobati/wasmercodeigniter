<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Models;

use CodeIgniter\Model;
use App\Modules\Gemini\Entities\Interaction;

/**
 * Manages storage and retrieval of user-AI interactions (memory).
 */
class InteractionModel extends Model
{
    protected $table            = 'interactions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'App\Modules\Gemini\Entities\Interaction';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'user_id',
        'unique_id',
        'timestamp',
        'user_input_raw',
        'ai_output',
        'ai_output_raw',
        'relevance_score',
        'last_accessed',
        'context_used_ids',
        'embedding',
        'keywords'
    ];
}
