<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int $id
 * @property string $subject
 * @property string $body
 * @property string $status
 * @property int $last_processed_id
 * @property int $sent_count
 * @property int $error_count
 * @property int $total_recipients
 * @property int $stop_at_count
 * @property int $max_user_id
 * @property string|null $quota_hit_at
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Campaign extends Entity
{
    protected $dates   = ['created_at', 'updated_at', 'quota_hit_at'];
    protected $casts   = [
        'id'                => 'integer',
        'subject'           => 'string',
        'body'              => 'string',
        'status'            => 'string',
        'last_processed_id' => 'integer',
        'sent_count'        => 'integer',
        'error_count'       => 'integer',
        'total_recipients'  => 'integer',
        'stop_at_count'     => 'integer',
        'max_user_id'       => 'integer',
    ];
}
