<?php declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int $id
 * @property int $user_id
 * @property bool $assistant_mode_enabled
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class UserSetting extends Entity
{
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'                     => 'integer',
        'user_id'                => 'integer',
        'assistant_mode_enabled' => 'boolean',
    ];
}
