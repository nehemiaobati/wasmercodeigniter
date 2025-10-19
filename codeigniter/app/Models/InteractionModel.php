<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Interaction;

class InteractionModel extends Model
{
    protected $table            = 'interactions';
    protected $primaryKey       = 'id';
    protected $returnType       = Interaction::class;
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'user_id', 'unique_id', 'timestamp', 'user_input_raw', 'ai_output',
        'relevance_score', 'last_accessed', 'context_used_ids', 'embedding', 'keywords'
    ];
}
