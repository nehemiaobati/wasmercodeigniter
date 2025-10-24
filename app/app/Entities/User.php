<?php declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int    $id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string $created_at
 * @property string $updated_at
 * @property float  $balance
 * @property bool   $is_admin
 * @property ?string $verification_token
 * @property bool   $is_verified
 * @property ?string $reset_token
 * @property ?string $reset_expires
 */
class User extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id'          => 'integer',
        'is_admin'    => 'boolean',
        'is_verified' => 'boolean',
        'balance'     => 'float',
    ];
}
