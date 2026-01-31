<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Modules\Admin\Models\CampaignModel; // Import the new model
use CodeIgniter\HTTP\RedirectResponse;

use App\Modules\Admin\Libraries\CampaignService; // Import the service
use CodeIgniter\API\ResponseTrait;

/**
 * Handles the creation and sending of email campaigns to all registered users.
 * This controller is intended for administrative use only.
 */
class CampaignController extends BaseController
{
    use ResponseTrait;

    /**
     * @var \CodeIgniter\HTTP\IncomingRequest
     */
    protected $request;

    /**
     * Displays the email campaign creation form.
     *
     * @return string|RedirectResponse The rendered view or a redirect if not an admin.
     */
    public function create(): string|RedirectResponse
    {
        if (!session()->get('is_admin')) {
            return redirect()->to(url_to('home'))->with('error', 'You do not have permission to access this page.');
        }

        helper('form');
        $id = $this->request->getGet('id');

        $campaignService = new CampaignService();
        $data = $campaignService->getDashboardData($id ? (int)$id : null);

        $data = array_merge($data, [
            'pageTitle'       => 'Create Email Campaign | Admin',
            'metaDescription' => 'Compose and send a new email campaign to all registered users.',
            'canonicalUrl'    => url_to('admin.campaign.create'),
            'robotsTag'       => 'noindex, nofollow',
        ]);

        return view('App\Modules\Admin\Views\campaign\create', $data);
    }

    /**
     * Saves the current subject and message as a new campaign template.
     *
     * @return RedirectResponse
     */
    public function save(): RedirectResponse
    {
        if (!session()->get('is_admin')) {
            return redirect()->to(url_to('home'))->with('error', 'You are not authorized.');
        }

        $rules = [
            'subject'       => 'required|min_length[5]|max_length[255]',
            'message'       => 'required|min_length[20]',
            'stop_at_count' => 'permit_empty|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $campaignService = new CampaignService();
        $result = $campaignService->saveCampaign([
            'id'            => $this->request->getPost('id'),
            'subject'       => $this->request->getPost('subject'),
            'message'       => $this->request->getPost('message'),
            'stop_at_count' => $this->request->getPost('stop_at_count'),
        ]);

        if ($result['success']) {
            return redirect()->to(url_to('admin.campaign.create') . '?id=' . $result['id'])->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Deletes a specific campaign template.
     *
     * @param int $id The ID of the campaign to delete.
     * @return RedirectResponse
     */
    public function delete(int $id): RedirectResponse
    {
        if (!session()->get('is_admin')) {
            return redirect()->to(url_to('home'))->with('error', 'You are not authorized.');
        }

        $campaignService = new CampaignService();
        $result = $campaignService->deleteCampaign($id);

        if ($result['success']) {
            return redirect()->to(url_to('admin.campaign.create'))->with('success', $result['message']);
        }

        return redirect()->to(url_to('admin.campaign.create'))->with('error', $result['message']);
    }

    /**
     * Creates and initiates a new campaign send.
     *
     * @return RedirectResponse
     */
    public function send(): RedirectResponse
    {
        if (!session()->get('is_admin')) {
            return redirect()->to(url_to('home'))->with('error', 'You are not authorized.');
        }

        $rules = [
            'subject'       => 'required|min_length[5]|max_length[255]',
            'message'       => 'required|min_length[20]',
            'stop_at_count' => 'permit_empty|integer'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $campaignService = new CampaignService();
        $result = $campaignService->createAndInitiate([
            'subject'       => $this->request->getPost('subject'),
            'message'       => $this->request->getPost('message'),
            'stop_at_count' => $this->request->getPost('stop_at_count'),
        ]);

        if (!$result['success']) {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }

        return redirect()->to(url_to('admin.campaign.monitor', $result['executionId']));
    }

    /**
     * Redirects to the monitor page for a specific campaign.
     *
     * @param int $id The ID of the campaign to monitor.
     * @return string|RedirectResponse
     */
    public function monitor(int $id): string|RedirectResponse
    {
        if (!session()->get('is_admin')) {
            return redirect()->to(url_to('home'))->with('error', 'Unauthorized.');
        }

        $campaignModel = new CampaignModel();
        $campaign = $campaignModel->find($id);

        if (!$campaign) {
            return redirect()->to(url_to('admin.campaign.create'))->with('error', 'Campaign not found.');
        }

        $data = [
            'campaign'        => $campaign,
            'pageTitle'       => 'Monitor Campaign | Admin',
            'metaDescription' => 'Live monitoring of email campaign delivery progress and SMTP health.',
            'canonicalUrl'    => url_to('admin.campaign.monitor', $id),
            'robotsTag'       => 'noindex, nofollow',
        ];

        return view('App\Modules\Admin\Views\campaign\monitor', $data);
    }

    /**
     * Processes a batch for the given campaign (Normal or Retry).
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\Response
     */
    public function process_batch(int $id)
    {
        if (!session()->get('is_admin')) {
            return $this->failForbidden('Unauthorized');
        }

        $campaignModel   = new CampaignModel();
        $campaign        = $campaignModel->find($id);

        if (!$campaign) {
            return $this->failNotFound('Campaign not found.');
        }

        $campaignService = new CampaignService();
        $result = ($campaign->status === 'retry_mode')
            ? $campaignService->processRetryBatch($id, 50)
            : $campaignService->processBatch($id, 50);

        return $this->respond($result);
    }

    /**
     * Initiates the retry mode for failed recipients.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function start_retry(int $id): RedirectResponse
    {
        if (!session()->get('is_admin')) {
            return redirect()->to(url_to('home'));
        }

        $campaignService = new CampaignService();
        $result = $campaignService->startRetry($id);

        return redirect()->to(url_to('admin.campaign.monitor', $id))->with('success', $result['message']);
    }

    /**
     * Pauses a campaign in the database.
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\Response
     */
    public function pause(int $id)
    {
        if (!session()->get('is_admin')) {
            return $this->failForbidden();
        }

        $campaignService = new CampaignService();
        $result = $campaignService->pauseCampaign($id);

        return $this->respond($result);
    }

    /**
     * Resumes a campaign via the service.
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\Response
     */
    public function resume(int $id)
    {
        if (!session()->get('is_admin')) {
            return $this->failForbidden();
        }

        $campaignService = new CampaignService();
        $result = $campaignService->resumeCampaign($id);

        return $this->respond($result);
    }
}
