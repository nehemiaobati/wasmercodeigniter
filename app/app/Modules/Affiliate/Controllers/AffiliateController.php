<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Controllers;

use App\Controllers\BaseController;
use App\Modules\Affiliate\Models\AffiliateLinkModel;
use App\Modules\Affiliate\Models\AffiliateCategoryModel;
use App\Modules\Affiliate\Libraries\AffiliateLinkService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * AffiliateController
 * 
 * Handles public redirects and admin management of Amazon affiliate links.
 */
class AffiliateController extends BaseController
{
    protected AffiliateLinkModel $affiliateModel;
    protected AffiliateLinkService $affiliateService;

    public function __construct()
    {
        $this->affiliateModel   = new AffiliateLinkModel();
        $this->affiliateService = new AffiliateLinkService($this->affiliateModel);
        helper('form');
    }

    // --- PUBLIC-FACING METHODS ---

    /**
     * Redirects to the full Amazon affiliate URL based on the short code.
     *
     * @param string $code The affiliate code (e.g., 3NCQfcG)
     * @return RedirectResponse
     * @throws PageNotFoundException If the code is not found
     */
    public function redirect(string $code): RedirectResponse
    {
        $link = $this->affiliateService->findByCode($code);

        if (!$link) {
            throw PageNotFoundException::forPageNotFound('The requested affiliate link was not found.');
        }

        // Service handles both detailed logging and simple click count increment
        $this->affiliateService->logClick($link->id, [
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->__toString(),
            'referrer'   => $this->request->getHeaderLine('Referer') ?: null,
        ]);

        // Redirect with 301 (permanent) for SEO benefits
        return redirect()->to($link->full_url, 301);
    }

    // --- ADMIN METHODS ---

    /**
     * Lists all affiliate links (admin only).
     *
     * @return string
     */
    public function index(): string
    {
        $filters = [
            'search'   => $this->request->getGet('search'),
            'status'   => $this->request->getGet('status'),
            'category' => $this->request->getGet('category') ? (int) $this->request->getGet('category') : null,
        ];

        $paginatedData = $this->affiliateService->getPaginatedLinks(15, $filters);

        $data = [
            'pageTitle'  => 'Manage Affiliate Links | Admin',
            'links'      => $paginatedData['links'],
            'pager'      => $paginatedData['pager'],
            'categories' => $this->affiliateService->getAllCategories(),
            'filters'    => $filters,
            'robotsTag'  => 'noindex, follow',
        ];

        return view('App\Modules\Affiliate\Views\admin\affiliate\index', $data);
    }

    /**
     * Displays the form to create a new affiliate link (admin only).
     *
     * @return string
     */
    public function create(): string
    {
        $data = [
            'pageTitle'  => 'Add New Affiliate Link | Admin',
            'formTitle'  => 'Add New Affiliate Link',
            'formAction' => url_to('admin.affiliate.store'),
            'link'       => null,
            'categories' => $this->affiliateService->getAllCategories(),
            'robotsTag'  => 'noindex, follow',
        ];

        return view('App\Modules\Affiliate\Views\admin\affiliate\form', $data);
    }

    /**
     * Saves a new affiliate link (admin only).
     *
     * @return RedirectResponse
     */
    public function store(): RedirectResponse
    {
        if ($this->affiliateService->saveLink($this->request->getPost())) {
            return redirect()->to(url_to('admin.affiliate.index'))->with('success', 'Affiliate link created successfully.');
        }

        return redirect()->back()->withInput()->with('errors', $this->affiliateService->getErrors());
    }

    /**
     * Displays the form to edit an existing affiliate link (admin only).
     *
     * @param int $id Link ID
     * @return string
     * @throws PageNotFoundException
     */
    public function edit(int $id): string
    {
        $link = $this->affiliateModel->find($id);
        if (!$link) {
            throw PageNotFoundException::forPageNotFound('Affiliate link not found.');
        }

        $data = [
            'pageTitle'  => 'Edit Affiliate Link | Admin',
            'formTitle'  => 'Edit Affiliate Link',
            'formAction' => url_to('admin.affiliate.update', $id),
            'link'       => $link,
            'categories' => $this->affiliateService->getAllCategories(),
            'robotsTag'  => 'noindex, follow',
        ];

        return view('App\Modules\Affiliate\Views\admin\affiliate\form', $data);
    }

    /**
     * Updates an existing affiliate link (admin only).
     *
     * @param int $id Link ID
     * @return RedirectResponse
     */
    public function update(int $id): RedirectResponse
    {
        if ($this->affiliateService->saveLink($this->request->getPost(), $id)) {
            return redirect()->to(url_to('admin.affiliate.index'))->with('success', 'Affiliate link updated successfully.');
        }

        return redirect()->back()->withInput()->with('errors', $this->affiliateService->getErrors());
    }

    /**
     * Deletes an affiliate link (admin only).
     *
     * @param int $id Link ID
     * @return RedirectResponse
     * @throws PageNotFoundException
     */
    public function delete(int $id): RedirectResponse
    {
        $link = $this->affiliateModel->find($id);
        if (!$link) {
            throw PageNotFoundException::forPageNotFound('Cannot delete a link that does not exist.');
        }

        if ($this->affiliateService->deleteLink($id)) {
            return redirect()->to(url_to('admin.affiliate.index'))->with('success', 'Affiliate link deleted successfully.');
        }

        return redirect()->to(url_to('admin.affiliate.index'))->with('error', 'Failed to delete the affiliate link.');
    }

    /**
     * Display analytics for a specific link.
     *
     * @param int $id Link ID
     * @return string
     * @throws PageNotFoundException
     */
    public function analytics(int $id): string
    {
        $link = $this->affiliateModel->find($id);
        if (!$link) {
            throw PageNotFoundException::forPageNotFound('Affiliate link not found.');
        }

        $period = $this->request->getGet('period') ?? '30days';
        $analytics = $this->affiliateService->getAnalytics($id, $period);

        $data = [
            'pageTitle'  => 'Analytics: ' . ($link->title ?: $link->code) . ' | Admin',
            'link'       => $link,
            'analytics'  => $analytics,
            'period'     => $period,
            'robotsTag'  => 'noindex, follow',
        ];

        return view('App\Modules\Affiliate\Views\admin\affiliate\analytics', $data);
    }

    /**
     * Handle bulk actions (delete or status change).
     *
     * @return RedirectResponse
     */
    public function bulkAction(): RedirectResponse
    {
        $action = $this->request->getPost('action');
        $ids    = $this->request->getPost('ids');

        if (empty($ids) || !is_array($ids)) {
            return redirect()->to(url_to('admin.affiliate.index'))->with('error', 'No links selected.');
        }

        $success = match ($action) {
            'delete'     => $this->affiliateService->bulkDelete($ids),
            'activate'   => $this->affiliateService->bulkUpdateStatus($ids, 'active'),
            'deactivate' => $this->affiliateService->bulkUpdateStatus($ids, 'inactive'),
            default      => false,
        };

        if ($success) {
            $message = match ($action) {
                'delete'     => count($ids) . ' link(s) deleted successfully.',
                'activate'   => count($ids) . ' link(s) activated successfully.',
                'deactivate' => count($ids) . ' link(s) deactivated successfully.',
                default      => 'Action completed.',
            };
            return redirect()->to(url_to('admin.affiliate.index'))->with('success', $message);
        }

        return redirect()->to(url_to('admin.affiliate.index'))->with('error', 'Failed to perform bulk action.');
    }

    // --- CATEGORY MANAGEMENT METHODS ---

    /**
     * List all categories.
     *
     * @return string
     */
    public function categories(): string
    {
        $data = [
            'pageTitle'  => 'Manage Categories | Admin',
            'categories' => $this->affiliateService->getCategoriesWithCounts(),
            'robotsTag'  => 'noindex, follow',
        ];

        return view('App\Modules\Affiliate\Views\admin\category\index', $data);
    }

    /**
     * Display form to create a new category.
     *
     * @return string
     */
    public function createCategory(): string
    {
        $data = [
            'pageTitle'  => 'Add New Category | Admin',
            'formTitle'  => 'Add New Category',
            'formAction' => url_to('admin.affiliate.category.store'),
            'category'   => null,
            'robotsTag'  => 'noindex, follow',
        ];

        return view('App\Modules\Affiliate\Views\admin\category\form', $data);
    }

    /**
     * Save a new category.
     *
     * @return RedirectResponse
     */
    public function storeCategory(): RedirectResponse
    {
        if ($this->affiliateService->saveCategory($this->request->getPost())) {
            return redirect()->to(url_to('admin.affiliate.categories'))->with('success', 'Category created successfully.');
        }

        return redirect()->back()->withInput()->with('errors', $this->affiliateService->getCategoryErrors());
    }

    /**
     * Display form to edit a category.
     *
     * @param int $id Category ID
     * @return string
     * @throws PageNotFoundException
     */
    public function editCategory(int $id): string
    {
        $category = $this->affiliateService->getCategory($id);
        if (!$category) {
            throw PageNotFoundException::forPageNotFound('Category not found.');
        }

        $data = [
            'pageTitle'  => 'Edit Category | Admin',
            'formTitle'  => 'Edit Category',
            'formAction' => url_to('admin.affiliate.category.update', $id),
            'category'   => $category,
            'robotsTag'  => 'noindex, follow',
        ];

        return view('App\Modules\Affiliate\Views\admin\category\form', $data);
    }

    /**
     * Update an existing category.
     *
     * @param int $id Category ID
     * @return RedirectResponse
     */
    public function updateCategory(int $id): RedirectResponse
    {
        if ($this->affiliateService->saveCategory($this->request->getPost(), $id)) {
            return redirect()->to(url_to('admin.affiliate.categories'))->with('success', 'Category updated successfully.');
        }

        return redirect()->back()->withInput()->with('errors', $this->affiliateService->getCategoryErrors());
    }

    /**
     * Delete a category.
     *
     * @param int $id Category ID
     * @return RedirectResponse
     * @throws PageNotFoundException
     */
    public function deleteCategory(int $id): RedirectResponse
    {
        $category = $this->affiliateService->getCategory($id);
        if (!$category) {
            throw PageNotFoundException::forPageNotFound('Cannot delete a category that does not exist.');
        }

        if ($this->affiliateService->deleteCategory($id)) {
            return redirect()->to(url_to('admin.affiliate.categories'))->with('success', 'Category deleted successfully.');
        }

        return redirect()->to(url_to('admin.affiliate.categories'))->with('error', 'Failed to delete the category. It may have links assigned.');
    }
}
