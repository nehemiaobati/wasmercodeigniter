<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Modules\Admin\Models\CampaignModel; // Import the new model
use CodeIgniter\HTTP\RedirectResponse;

use App\Modules\Admin\Services\CampaignService; // Import the service
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
        $campaignModel = new CampaignModel();

        // Check for cooldown (last quota hit across ANY campaign)
        $lastQuotaHit = $campaignModel->where('quota_hit_at IS NOT NULL')
            ->orderBy('quota_hit_at', 'DESC')
            ->first();

        $userModel = new UserModel();
        $totalUserCount = $userModel->countAllResults();

        $data = [
            'pageTitle'       => 'Create Email Campaign | Admin',
            'metaDescription' => 'Compose and send a new email campaign to all registered users.',
            'canonicalUrl'    => url_to('admin.campaign.create'),
            'robotsTag'       => 'noindex, nofollow',
            'campaigns'       => $campaignModel->orderBy('created_at', 'DESC')->paginate(10), // Limit to 10 per page
            'pager'           => $campaignModel->pager,
            'lastQuotaHit'    => $lastQuotaHit ? $lastQuotaHit->quota_hit_at : null,
            'totalUserCount'  => $totalUserCount
        ];

        return view('App\Modules\Admin\Views\campaign\create', $data);
    }

    /**
     * Saves the current subject and message as a new campaign template.
     *
     * @return RedirectResponse
     */
    public function save(): RedirectResponse
    {
        // Security check
        if (!session()->get('is_admin')) {
            return redirect()->to(url_to('home'))->with('error', 'You are not authorized to perform this action.');
        }

        // Validation rules
        $rules = [
            'subject' => 'required|min_length[5]|max_length[255]',
            'message' => 'required|min_length[20]',
        ];

        if (!$this->validate($rules)) {
            // Redirect back with input and errors if validation fails
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $campaignModel = new CampaignModel();
        $data = [
            'subject' => $this->request->getPost('subject'),
            'body'    => $this->request->getPost('message'),
        ];

        if ($campaignModel->save($data)) {
            // Redirect to the create page with a success message
            return redirect()->to(url_to('admin.campaign.create'))->with('success', 'Campaign template saved successfully.');
        }

        // Redirect back with input and an error message if saving fails
        return redirect()->back()->withInput()->with('error', 'Failed to save the campaign template.');
    }

    /**
     * Deletes a specific campaign template.
     *
     * @param int $id The ID of the campaign to delete.
     * @return RedirectResponse
     */
    public function delete(int $id): RedirectResponse
    {
        // Security check
        if (!session()->get('is_admin')) {
            return redirect()->to(url_to('home'))->with('error', 'You are not authorized to perform this action.');
        }

        $campaignModel = new CampaignModel();
        $campaign = $campaignModel->find($id);

        if (!$campaign) {
            return redirect()->to(url_to('admin.campaign.create'))->with('error', 'Campaign template not found.');
        }

        if ($campaignModel->delete($id)) {
            return redirect()->to(url_to('admin.campaign.create'))->with('success', 'Campaign template deleted successfully.');
        }

        return redirect()->to(url_to('admin.campaign.create'))->with('error', 'Failed to delete the campaign template.');
    }

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

        $subject     = $this->request->getPost('subject');
        $messageBody = $this->request->getPost('message');
        $stopAtCount = (int)$this->request->getPost('stop_at_count') ?: 1000; // Default 1000

        // Sanitize
        $allowed_tags = '<p><a><strong><em><ul><ol><li><br><h1><h2><h3><h4><h5><h6>';
        $messageBody  = str_replace('[your_base_url]', rtrim(base_url(), '/'), $messageBody);
        $sanitizedMessageBody = strip_tags($messageBody, $allowed_tags);

        // Save Campaign to get ID
        $campaignModel = new CampaignModel();
        $campaignData = [
            'subject'       => $subject,
            'body'          => $sanitizedMessageBody,
            'status'        => 'draft',
            'stop_at_count' => $stopAtCount
        ];

        $campaignId = $campaignModel->insert($campaignData);

        if (!$campaignId) {
            return redirect()->back()->withInput()->with('error', 'Failed to create campaign record.');
        }

        // Initiate via Service
        $campaignService = new CampaignService();
        $result = $campaignService->initiateCampaign((int)$campaignId);

        if (!$result['success']) {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }

        // Redirect to Monitor
        return redirect()->to(url_to('admin.campaign.monitor', $campaignId));
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

        $campaignService = new CampaignService();
        $campaignModel   = new CampaignModel();
        $campaign        = $campaignModel->find($id);

        if (!$campaign) {
            return $this->failNotFound('Campaign not found.');
        }

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

        $campaignModel = new CampaignModel();
        $campaignModel->update($id, ['status' => 'retry_mode']);

        return redirect()->to(url_to('admin.campaign.monitor', $id))->with('success', 'Retry process initiated.');
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

        $campaignModel = new CampaignModel();
        $campaignModel->update($id, ['status' => 'paused']);

        return $this->respond(['status' => 'success', 'message' => 'Campaign paused.']);
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
