<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Models;

use CodeIgniter\Model;
use App\Modules\Affiliate\Entities\AffiliateLink;

/**
 * AffiliateLinkModel
 * 
 * Handles database operations for Amazon affiliate links.
 */
class AffiliateLinkModel extends Model
{
    protected $table            = 'affiliate_links';
    protected $primaryKey       = 'id';
    protected $returnType       = AffiliateLink::class;
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'code',
        'short_url',
        'full_url',
        'title',
        'click_count',
        'status'
    ];

    /**
     * @var array<string, string>
     */
    protected $validationRules = [
        'code'      => 'required|max_length[50]|is_unique[affiliate_links.code,id,{id}]',
        'short_url' => 'required|max_length[255]|valid_url',
        'full_url'  => 'required|valid_url',
        'title'     => 'permit_empty|max_length[255]',
        'status'    => 'permit_empty|in_list[active,inactive]',
    ];

    /**
     * @var array<string, string>
     */
    protected $validationMessages = [
        'code' => [
            'required'  => 'The affiliate code is required.',
            'is_unique' => 'This affiliate code already exists.',
        ],
        'short_url' => [
            'required'   => 'The short URL is required.',
            'valid_url'  => 'Please provide a valid short URL.',
        ],
        'full_url' => [
            'required'  => 'The full URL is required.',
            'valid_url' => 'Please provide a valid full URL.',
        ],
    ];
}
