<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Entities;

use CodeIgniter\Entity\Entity;

/**
 * AffiliateLink Entity
 * 
 * Represents an Amazon affiliate link with its short code and tracking data.
 */
class AffiliateLink extends Entity
{
    /**
     * @var array<string, string>
     */
    protected $casts = [
        'id'          => 'integer',
        'click_count' => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * @var array<int, string>
     */
    protected $dates = ['created_at', 'updated_at'];
}
