<?php declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int $id
 * @property int $user_id
 * @property string $entity_key
 * @property string $name
 * @property string $type
 * @property int $access_count
 * @property float $relevance_score
 * @property string|null $mentioned_in
 * @property string|null $relationships
 */
class AGIEntity extends Entity
{
    protected $table = 'entities';
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id' => 'integer',
        'user_id' => 'integer',
        'access_count' => 'integer',
        'relevance_score' => 'float',
        'mentioned_in' => 'json-array',
        'relationships' => 'json-array',
    ];
}
