<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Libraries;

use App\Modules\Affiliate\Models\AffiliateLinkModel;
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

    /**
     * Constructor.
     *
     * @param AffiliateLinkModel|null $model
     */
    public function __construct(?AffiliateLinkModel $model = null)
    {
        $this->model = $model ?? new AffiliateLinkModel();
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
            'short_url' => $data['short_url'] ?? null,
            'full_url'  => $data['full_url'] ?? null,
            'title'     => $data['title'] ?? null,
            'status'    => $data['status'] ?? 'active',
        ];

        // Auto-extract code from short_url if not provided
        if (!empty($payload['short_url'])) {
            $payload['code'] = $data['code'] ?? $this->_extractCodeFromUrl($payload['short_url']);
        }

        return $payload;
    }

    // --- Public Methods ---

    /**
     * Retrieves paginated affiliate links.
     *
     * @param int $perPage Number of links per page
     * @return array{links: mixed, pager: \CodeIgniter\Pager\Pager}
     */
    public function getPaginatedLinks(int $perPage = 15): array
    {
        return [
            'links' => $this->model->orderBy('created_at', 'DESC')->paginate($perPage),
            'pager' => $this->model->pager,
        ];
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
    public function incrementClickCount(int $id): void
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
}
