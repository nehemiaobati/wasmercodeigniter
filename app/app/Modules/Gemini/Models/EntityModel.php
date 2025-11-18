<?php declare(strict_types=1);

namespace App\Modules\Gemini\Models;

use CodeIgniter\Model;
use App\Modules\Gemini\Entities\AGIEntity;

class EntityModel extends Model
{
    protected $table            = 'entities';
    protected $primaryKey       = 'id';
    protected $returnType       = 'App\Modules\Gemini\Entities\AGIEntity';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'user_id', 'entity_key', 'name', 'type', 'access_count',
        'relevance_score', 'mentioned_in', 'relationships'
    ];

    /**
     * Finds an entity by its key for a specific user.
     * @param int $userId
     * @param string $entityKey
     * @return AGIEntity|null
     */
    public function findByUserAndKey(int $userId, string $entityKey): ?AGIEntity
    {
        return $this->where('user_id', $userId)
                    ->where('entity_key', $entityKey)
                    ->first();
    }
}
