<?php declare(strict_types=1);

namespace App\Modules\Ollama\Entities;

use CodeIgniter\Entity\Entity;

/**
 * Represents a concept or keyword tracked in the local AI memory.
 *
 * @property int $id
 * @property int $user_id
 * @property string $entity_key      Lowercase unique key (e.g., "php")
 * @property string $name            Display name (e.g., "PHP")
 * @property int $access_count       How many times this concept has been discussed
 * @property float $relevance_score  Current weight/importance of this concept
 * @property array $mentioned_in     List of Interaction IDs where this appears
 * @property \CodeIgniter\I18n\Time $created_at
 * @property \CodeIgniter\I18n\Time $updated_at
 */
class OllamaEntity extends Entity
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