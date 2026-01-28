<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Entities;

use CodeIgniter\Entity\Entity;

/**
 * AffiliateCategory Entity
 * 
 * Represents a category for organizing affiliate links.
 */
class AffiliateCategory extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'          => 'integer',
        'name'        => 'string',
        'slug'        => 'string',
        'description' => '?string',
    ];
}
