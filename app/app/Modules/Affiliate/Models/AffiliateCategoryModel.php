<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Models;

use App\Modules\Affiliate\Entities\AffiliateCategory;
use CodeIgniter\Model;

/**
 * AffiliateCategoryModel
 * 
 * Handles database operations for affiliate categories.
 */
class AffiliateCategoryModel extends Model
{
    protected $table            = 'affiliate_categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = AffiliateCategory::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'slug',
        'description',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'id'          => 'permit_empty',
        'name'        => 'required|max_length[100]|is_unique[affiliate_categories.name,id,{id}]',
        'slug'        => 'required|max_length[100]|alpha_dash|is_unique[affiliate_categories.slug,id,{id}]',
        'description' => 'permit_empty',
    ];

    protected $validationMessages = [
        'name' => [
            'required'  => 'Category name is required.',
            'is_unique' => 'This category name already exists.',
        ],
        'slug' => [
            'required'   => 'Category slug is required.',
            'alpha_dash' => 'Slug can only contain letters, numbers, dashes, and underscores.',
            'is_unique'  => 'This slug already exists.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Get all categories with their link counts.
     *
     * @return array
     */
    public function getCategoriesWithCounts(): array
    {
        return $this->select('affiliate_categories.*, COUNT(affiliate_links.id) as link_count')
            ->join('affiliate_links', 'affiliate_links.category_id = affiliate_categories.id', 'left')
            ->groupBy('affiliate_categories.id')
            ->orderBy('affiliate_categories.name', 'ASC')
            ->findAll();
    }
}
