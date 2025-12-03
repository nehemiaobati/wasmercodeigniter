<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\CampaignModel; // Import the new model
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Handles the creation and sending of email campaigns to all registered users.
 * This controller is intended for administrative use only.
 */
class CampaignController extends BaseController
{


    /**
     * Displays the email campaign creation form.
     *
     * @return string|RedirectResponse The rendered view or a redirect if not an admin.
     */
    public function create(): string|RedirectResponse
    {
        // Security check: Ensure only administrators can access this page.
        if (!session()->get('is_admin')) {
            return redirect()->to(url_to('home'))->with('error', 'You do not have permission to access this page.');
        }

        helper('form');
        $campaignModel = new CampaignModel();

        $data = [
            'pageTitle'       => 'Create Email Campaign | Admin',
            'metaDescription' => 'Compose and send a new email campaign to all registered users.',
            'canonicalUrl'    => url_to('admin.campaign.create'),
            // Fetch campaigns ordered by creation date, descending
            'campaigns'       => $campaignModel->orderBy('created_at', 'DESC')->findAll(),
        ];

        return view('admin/campaign/create', $data);
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

    /**
     * Processes the campaign form submission and sends the email to all users.
     *
     * @return RedirectResponse
     */
    public function send(): RedirectResponse
    {
        // Security check: Ensure only administrators can perform this action.
        if (!session()->get('is_admin')) {
            return redirect()->to(url_to('home'))->with('error', 'You are not authorized to perform this action.');
        }

        // 1. Validate the form input.
        $rules = [
            'subject' => 'required|min_length[5]|max_length[255]',
            'message' => 'required|min_length[20]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $subject = $this->request->getPost('subject');
        $messageBody = $this->request->getPost('message');

        // Define allowed tags for sanitization.
        $allowed_tags = '<p><a><strong><em><ul><ol><li><br><h1><h2><h3><h4><h5><h6>';

        // Replace [your_base_url] placeholder with the actual base URL.
        $messageBody = str_replace('[your_base_url]', rtrim(base_url(), '/'), $messageBody);

        // Sanitize the message body before use.
        $sanitizedMessageBody = strip_tags($messageBody, $allowed_tags);

        // 2. Fetch all users with their emails and usernames.
        $userModel = new UserModel();
        $users = $userModel->select('email, username')->findAll();

        if (empty($users)) {
            return redirect()->back()->with('error', 'No users found to send the campaign to.');
        }

        // 3. Configure and send individual emails.
        $emailService = service('email');
        $successCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            // Prepare the dynamic content for the email template, including username.
            $emailData = [
                'subject' => $subject,
                'body_content' => $sanitizedMessageBody, // Pass the sanitized content.
                'username' => $user->username, // Pass the username for personalization.
            ];

            // Set recipient to the current user's email.
            $emailService->setTo($user->email);
            $emailService->setSubject($subject);
            $emailService->setMessage(view('emails/campaign_email', $emailData));

            if ($emailService->send()) {
                $successCount++;
            } else {
                $failedCount++;
                // Log the error for this specific user.
                log_message('error', 'Campaign email sending failed for user ' . $user->email . ': ' . $emailService->printDebugger(['headers']));
            }
            // It's good practice to clear recipients if the email service object is reused,
            // though CodeIgniter's service might handle this internally.
            // $emailService->clearTo();
        }

        // Provide feedback based on the number of successful and failed sends.
        if ($failedCount === 0) {
            return redirect()->to(url_to('admin.campaign.create'))->with('success', "Campaign sent successfully to {$successCount} users.");
        } else {
            $errorMessage = "Campaign sent to {$successCount} users. Failed for {$failedCount} users. Please check the logs for details.";
            // Optionally, you could also redirect back with input if needed, but a general error is fine here.
            return redirect()->to(url_to('admin.campaign.create'))->with('error', $errorMessage);
        }
    }
}
