<?php declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int $id
 * @property string $subject
 * @property string $body
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Campaign extends Entity
{
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'      => 'integer',
        'subject' => 'string',
        'body'    => 'string',
    ];
}
