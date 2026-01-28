<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Models;

use App\Modules\Affiliate\Entities\AffiliateClickLog;
use CodeIgniter\Model;

/**
 * AffiliateClickLogModel
 * 
 * Handles database operations for affiliate click logs.
 */
class AffiliateClickLogModel extends Model
{
    protected $table            = 'affiliate_click_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = AffiliateClickLog::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'affiliate_link_id',
        'ip_address',
        'user_agent',
        'referrer',
        'clicked_at',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    /**
     * Get click logs for a specific link with optional date filtering.
     *
     * @param int $linkId The affiliate link ID
     * @param string|null $startDate Start date (Y-m-d format)
     * @param string|null $endDate End date (Y-m-d format)
     * @return array
     */
    public function getLogsByLinkId(int $linkId, ?string $startDate = null, ?string $endDate = null): array
    {
        $builder = $this->where('affiliate_link_id', $linkId);

        if ($startDate) {
            $builder->where('clicked_at >=', $startDate . ' 00:00:00');
        }

        if ($endDate) {
            $builder->where('clicked_at <=', $endDate . ' 23:59:59');
        }

        return $builder->orderBy('clicked_at', 'DESC')->findAll();
    }

    /**
     * Get click count grouped by date for a specific link.
     *
     * @param int $linkId The affiliate link ID
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @return array Array of ['date' => 'Y-m-d', 'count' => int]
     */
    public function getClickCountByDateRange(int $linkId, string $startDate, string $endDate): array
    {
        return $this->select('DATE(clicked_at) as date, COUNT(*) as count')
            ->where('affiliate_link_id', $linkId)
            ->where('clicked_at >=', $startDate . ' 00:00:00')
            ->where('clicked_at <=', $endDate . ' 23:59:59')
            ->groupBy('DATE(clicked_at)')
            ->orderBy('date', 'ASC')
            ->findAll();
    }

    /**
     * Get top referrers for a specific link.
     *
     * @param int $linkId The affiliate link ID
     * @param int $limit Number of top referrers to return
     * @return array Array of ['referrer' => string, 'count' => int]
     */
    public function getTopReferrers(int $linkId, int $limit = 10): array
    {
        return $this->select('referrer, COUNT(*) as count')
            ->where('affiliate_link_id', $linkId)
            ->where('referrer IS NOT NULL')
            ->where('referrer !=', '')
            ->groupBy('referrer')
            ->orderBy('count', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
