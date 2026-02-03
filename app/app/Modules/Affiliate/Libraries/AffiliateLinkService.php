<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Libraries;

use App\Modules\Affiliate\Models\AffiliateLinkModel;
use App\Modules\Affiliate\Models\AffiliateClickLogModel;
use App\Modules\Affiliate\Models\AffiliateCategoryModel;
use App\Modules\Affiliate\Entities\AffiliateLink;

/**
 * AffiliateLinkService
 * 
 * Business logic layer for managing Amazon affiliate links.
 * Handles creation, updates, deletion, and click tracking.
 */
class AffiliateLinkService
{
    protected AffiliateLinkModel $model;
    protected AffiliateClickLogModel $clickLogModel;
    protected AffiliateCategoryModel $categoryModel;

    /**
     * Constructor.
     *
     * @param AffiliateLinkModel|null $model
     * @param AffiliateClickLogModel|null $clickLogModel
     * @param AffiliateCategoryModel|null $categoryModel
     */
    public function __construct(
        ?AffiliateLinkModel $model = null,
        ?AffiliateClickLogModel $clickLogModel = null,
        ?AffiliateCategoryModel $categoryModel = null
    ) {
        $this->model         = $model         ?? new AffiliateLinkModel();
        $this->clickLogModel = $clickLogModel ?? new AffiliateClickLogModel();
        $this->categoryModel = $categoryModel ?? new AffiliateCategoryModel();
    }

    // --- Helper Methods ---

    /**
     * Extracts the code from an Amazon short URL.
     *
     * @param string $url Amazon short URL (e.g., https://amzn.to/3NCQfcG)
     * @return string|null The extracted code or null if invalid
     */
    private function _extractCodeFromUrl(string $url): ?string
    {
        // Match Amazon short URLs like https://amzn.to/3NCQfcG
        if (preg_match('#amzn\.to/([a-zA-Z0-9]+)#i', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Prepares the data payload for saving.
     *
     * @param array $data Raw input data
     * @return array Prepared data array
     */
    private function _preparePayload(array $data): array
    {
        $payload = [
            'short_url'   => $data['short_url'] ?? null,
            'full_url'    => $data['full_url'] ?? null,
            'title'       => $data['title'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'status'      => $data['status'] ?? 'active',
        ];

        // Auto-extract code from short_url if not provided
        if (!empty($payload['short_url'])) {
            $payload['code'] = $data['code'] ?? $this->_extractCodeFromUrl($payload['short_url']);
        }

        return $payload;
    }

    // --- Public Methods ---

    /**
     * Retrieves paginated and filtered affiliate links.
     *
     * @param int $perPage Number of links per page
     * @param array $filters Search/Filter parameters (search, status, category)
     * @return array{links: mixed, pager: \CodeIgniter\Pager\Pager}
     */
    public function getPaginatedLinks(int $perPage = 15, array $filters = []): array
    {
        $builder = $this->model;

        // Search by title or code
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                ->like('title', $filters['search'])
                ->orLike('code', $filters['search'])
                ->groupEnd();
        }

        // Filter by status
        if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'])) {
            $builder = $builder->where('status', $filters['status']);
        }

        // Filter by category
        if (!empty($filters['category'])) {
            $builder = $builder->where('category_id', $filters['category']);
        }

        return [
            'links' => $builder->orderBy('created_at', 'DESC')->paginate($perPage),
            'pager' => $this->model->pager,
        ];
    }

    /**
     * Checks if the given URL belongs to an allowed affiliate domain.
     *
     * @param string $url The URL to check
     * @return bool
     */
    private function _isValidAffiliateDomain(string $url): bool
    {
        $allowedDomains = [
            'amazon.com',
            'amazon.co.uk',
            'amzn.to',
            'www.amazon.com',
            'www.amazon.co.uk',
        ];

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        foreach ($allowedDomains as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Saves an affiliate link (created or updated).
     *
     * @param array $data Raw POST data
     * @param int|null $id Link ID for updates
     * @return bool True if successful, false otherwise
     */
    public function saveLink(array $data, ?int $id = null): bool
    {
        $payload = $this->_preparePayload($data);

        // Security: Validate full_url domain
        if (!empty($payload['full_url']) && !$this->_isValidAffiliateDomain($payload['full_url'])) {
            $this->model->setErrors(['full_url' => 'The provided URL must be a valid Amazon affiliate link.']);
            return false;
        }

        if ($id !== null) {
            $payload['id'] = $id;
        }

        $this->model->db->transStart();
        $result = (bool) $this->model->save($payload);
        $this->model->db->transComplete();

        return $this->model->db->transStatus() !== false && $result;
    }

    /**
     * Deletes an affiliate link.
     *
     * @param int $id Link ID
     * @return bool True if successful, false otherwise
     */
    public function deleteLink(int $id): bool
    {
        $this->model->db->transStart();
        $result = (bool) $this->model->delete($id);
        $this->model->db->transComplete();

        return $this->model->db->transStatus() !== false && $result;
    }

    /**
     * Finds an affiliate link by its code.
     *
     * @param string $code The short code (e.g., 3NCQfcG)
     * @return AffiliateLink|null The affiliate link entity or null
     */
    public function findByCode(string $code): ?AffiliateLink
    {
        return $this->model->where('code', $code)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Increments the click count for tracking.
     *
     * @param int $id Link ID
     * @return void
     */
    private function _incrementClickCount(int $id): void
    {
        $this->model->set('click_count', 'click_count + 1', false)
            ->where('id', $id)
            ->update();
    }

    /**
     * Returns validation errors from the model.
     *
     * @return array Validation errors
     */
    public function getErrors(): array
    {
        return $this->model->errors();
    }

    /**
     * Extracts the code from an Amazon short URL.
     *
     * @param string $url Amazon short URL
     * @return string|null Extracted code or null
     */
    public function extractCodeFromUrl(string $url): ?string
    {
        return $this->_extractCodeFromUrl($url);
    }


    /**
     * Log a click event and increment total click count.
     *
     * @param int $linkId The affiliate link ID
     * @param array $clickData Click data (ip_address, user_agent, referrer)
     * @return bool
     */
    public function logClick(int $linkId, array $clickData): bool
    {
        $logEntry = [
            'affiliate_link_id' => $linkId,
            'ip_address'        => $clickData['ip_address'] ?? null,
            'user_agent'        => $clickData['user_agent'] ?? null,
            'referrer'          => $clickData['referrer'] ?? null,
            'clicked_at'        => date('Y-m-d H:i:s'),
        ];

        // Increment total count
        $this->_incrementClickCount($linkId);

        return (bool) $this->clickLogModel->insert($logEntry);
    }

    /**
     * Get analytics data for a specific link.
     *
     * @param int $linkId The affiliate link ID
     * @param string $period Period to analyze (7days, 30days, 90days, all)
     * @return array Analytics data
     */
    public function getAnalytics(int $linkId, string $period = '30days'): array
    {
        // Calculate date range
        $endDate = date('Y-m-d');
        $startDate = match ($period) {
            '7days'  => date('Y-m-d', strtotime('-7 days')),
            '90days' => date('Y-m-d', strtotime('-90 days')),
            'all'    => '2000-01-01',
            default  => date('Y-m-d', strtotime('-30 days')),
        };

        return [
            'clicks_by_date' => $this->clickLogModel->getClickCountByDateRange($linkId, $startDate, $endDate),
            'top_referrers'  => $this->clickLogModel->getTopReferrers($linkId, 5),
            'recent_clicks'  => $this->clickLogModel->getLogsByLinkId($linkId, null, null),
            'period'         => $period,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
        ];
    }

    /**
     * Bulk delete affiliate links.
     *
     * @param array $ids Array of link IDs to delete
     * @return bool
     */
    public function bulkDelete(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        $this->model->db->transStart();

        foreach ($ids as $id) {
            $this->model->delete($id);
        }

        $this->model->db->transComplete();

        return $this->model->db->transStatus() !== false;
    }

    /**
     * Bulk update status for multiple links.
     *
     * @param array $ids Array of link IDs
     * @param string $status New status (active/inactive)
     * @return bool
     */
    public function bulkUpdateStatus(array $ids, string $status): bool
    {
        if (empty($ids) || !in_array($status, ['active', 'inactive'])) {
            return false;
        }

        $this->model->db->transStart();

        $this->model->whereIn('id', $ids)
            ->set('status', $status)
            ->update();

        $this->model->db->transComplete();

        return $this->model->db->transStatus() !== false;
    }

    // --- Category Management ---

    /**
     * Returns all categories.
     *
     * @return array
     */
    public function getAllCategories(): array
    {
        return $this->categoryModel->orderBy('name', 'ASC')->findAll();
    }

    /**
     * Returns categories with link counts.
     *
     * @return array
     */
    public function getCategoriesWithCounts(): array
    {
        return $this->categoryModel->getCategoriesWithCounts();
    }

    /**
     * Finds a category by ID.
     *
     * @param int $id Category ID
     * @return object|null
     */
    public function getCategory(int $id): ?object
    {
        return $this->categoryModel->find($id);
    }

    /**
     * Saves a category (create or update).
     *
     * @param array $data Input data
     * @param int|null $id Category ID for updates
     * @return bool
     */
    public function saveCategory(array $data, ?int $id = null): bool
    {
        if ($id !== null) {
            $data['id'] = $id;
        }

        $this->categoryModel->db->transStart();
        $result = (bool) $this->categoryModel->save($data);
        $this->categoryModel->db->transComplete();

        return $this->categoryModel->db->transStatus() !== false && $result;
    }

    /**
     * Deletes a category.
     *
     * @param int $id Category ID
     * @return bool
     */
    public function deleteCategory(int $id): bool
    {
        $this->categoryModel->db->transStart();
        $result = (bool) $this->categoryModel->delete($id);
        $this->categoryModel->db->transComplete();

        return $this->categoryModel->db->transStatus() !== false && $result;
    }

    /**
     * Returns category validation errors.
     *
     * @return array
     */
    public function getCategoryErrors(): array
    {
        return $this->categoryModel->errors();
    }
}
