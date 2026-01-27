<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Controllers;

use App\Controllers\BaseController;
use App\Modules\Affiliate\Models\AffiliateLinkModel;
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

        // Track the click
        $this->affiliateService->incrementClickCount($link->id);

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
        $paginatedData = $this->affiliateService->getPaginatedLinks(15);

        $data = [
            'pageTitle' => 'Manage Affiliate Links | Admin',
            'links'     => $paginatedData['links'],
            'pager'     => $paginatedData['pager'],
            'robotsTag' => 'noindex, follow',
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
}
