<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Entities;

use CodeIgniter\Entity\Entity;

/**
 * AffiliateClickLog Entity
 * 
 * Represents a single click event on an affiliate link.
 */
class AffiliateClickLog extends Entity
{
    protected $datamap = [];
    protected $dates   = ['clicked_at'];
    protected $casts   = [
        'id'                => 'integer',
        'affiliate_link_id' => 'integer',
        'ip_address'        => 'string',
        'user_agent'        => 'string',
        'referrer'          => '?string',
    ];
}
