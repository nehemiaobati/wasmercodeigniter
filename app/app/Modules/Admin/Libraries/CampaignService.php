<?php

declare(strict_types=1);

namespace App\Modules\Admin\Libraries;

use App\Modules\Admin\Models\CampaignModel;
use App\Models\UserModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Handles the business logic for email campaigns.
 * Manages recipient snapshots, batch processing, SMTP health, and data persistence.
 */
class CampaignService
{
    /** @var CampaignModel */
    protected $campaignModel;

    /** @var UserModel */
    protected $userModel;

    /** @var BaseConnection */
    protected $db;

    public function __construct()
    {
        $this->campaignModel = new CampaignModel();
        $this->userModel     = new UserModel();
        $this->db            = Database::connect();
    }

    // --- Helper Methods ---

    /**
     * Core sending logic used by both normal and retry batches.
     * Logs failures to campaign_logs table.
     *
     * @param object $campaign The campaign entity.
     * @param array $users Array of user objects to email.
     * @return array Contains sent count, error count, and last processed user ID.
     */
    private function _sendBatch(object $campaign, array $users): array
    {
        $emailService = Services::email();
        $fromEmail    = config('Email')->fromEmail;
        $fromName     = config('Email')->fromName;

        $sentCount  = 0;
        $errorCount = 0;
        $lastId     = 0;

        foreach ($users as $user) {
            $emailService->setFrom($fromEmail, $fromName);
            $emailService->setTo($user->email);
            $emailService->setSubject($campaign->subject);

            $emailData = [
                'subject'      => $campaign->subject,
                'body_content' => $campaign->body,
                'username'     => $user->username,
            ];

            $emailService->setMessage(view('App\Modules\Admin\Views\emails\campaign_email', $emailData));

            if ($emailService->send()) {
                $sentCount++;
            } else {
                $errorCount++;
                $errorMsg = $emailService->printDebugger(['headers']);

                // Keep logging failures for retry mode and monitoring
                $this->db->table('campaign_logs')->insert([
                    'campaign_id'   => $campaign->id,
                    'user_id'       => $user->id,
                    'status'        => 'failed',
                    'error_message' => substr($errorMsg, 0, 500),
                    'created_at'    => date('Y-m-d H:i:s')
                ]);
                log_message('error', "[CampaignService] Send failed for {$user->email}");
            }

            $emailService->clear();
            $lastId = $user->id;
        }

        return [
            'sent'    => $sentCount,
            'errors'  => $errorCount,
            'last_id' => $lastId
        ];
    }

    /**
     * Calculates the actual batch size based on remaining quota.
     *
     * @param object $campaign The campaign entity.
     * @param int $requestedBatchSize The maximum size requested.
     * @return int The calculated batch size.
     */
    private function _calculateBatchSize(object $campaign, int $requestedBatchSize): int
    {
        if ($campaign->stop_at_count <= 0) {
            return $requestedBatchSize;
        }

        $remainingQuota = $campaign->stop_at_count - $campaign->sent_count;
        return max(0, min($remainingQuota, $requestedBatchSize));
    }

    /**
     * Checks if the quota has been hit.
     *
     * @param object $campaign The campaign entity.
     * @param int $currentSentCount The total sent so far.
     * @return bool True if quota is hit.
     */
    private function _isQuotaHit(object $campaign, int $currentSentCount): bool
    {
        return $campaign->stop_at_count > 0 && $currentSentCount >= $campaign->stop_at_count;
    }

    /**
     * Sanitizes and parses the campaign message body.
     *
     * @param string $body The raw message body.
     * @return string The sanitized content.
     */
    private function _prepareMessage(string $body): string
    {
        $allowed_tags = '<p><a><strong><em><ul><ol><li><br><h1><h2><h3><h4><h5><h6>';
        $body = str_replace('[your_base_url]', rtrim(base_url(), '/'), $body);
        return strip_tags($body, $allowed_tags);
    }

    // --- Public Methods ---

    /**
     * Fetches all data required for the campaign creation dashboard.
     *
     * @param int|null $editingId Optional ID of a campaign being edited.
     * @return array Data for the view.
     */
    public function getDashboardData(?int $editingId = null): array
    {
        $editingCampaign = $editingId ? $this->campaignModel->find($editingId) : null;

        $lastQuotaHit = $this->campaignModel->where('quota_hit_at IS NOT NULL')
            ->orderBy('quota_hit_at', 'DESC')
            ->first();

        $totalUserCount = $this->userModel->countAllResults();

        $drafts = $this->campaignModel->where('status', 'draft')
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        $history = $this->campaignModel->where('status !=', 'draft')
            ->orderBy('created_at', 'DESC')
            ->paginate(5);

        return [
            'editingCampaign' => $editingCampaign,
            'lastQuotaHit'    => $lastQuotaHit ? $lastQuotaHit->quota_hit_at : null,
            'totalUserCount'  => $totalUserCount,
            'drafts'          => $drafts,
            'campaigns'       => $history,
            'pager'           => $this->campaignModel->pager,
        ];
    }

    /**
     * Saves a campaign draft or template.
     *
     * @param array $data Input data from the controller.
     * @return array Success/Error status and message.
     */
    public function saveCampaign(array $data): array
    {
        $this->db->transStart();

        $campaignData = [
            'subject'       => $data['subject'],
            'body'          => $data['message'],
            'stop_at_count'   => (int)($data['stop_at_count'] ?? 1000) ?: 1000,
            'quota_increment' => (int)($data['stop_at_count'] ?? 1000) ?: 1000,
            'status'          => 'draft',
        ];

        if (!empty($data['id'])) {
            $campaignData['id'] = $data['id'];
        }

        if (!$this->campaignModel->save($campaignData)) {
            $this->db->transRollback();
            return ['success' => false, 'message' => 'Failed to save the campaign template.'];
        }

        $insertedId = $data['id'] ?? $this->campaignModel->getInsertID();
        $this->db->transComplete();

        return [
            'success' => true,
            'message' => 'Campaign template saved successfully.',
            'id'      => $insertedId
        ];
    }

    /**
     * Deletes a campaign template.
     *
     * @param int $id The ID of the campaign to delete.
     * @return array Success/Error status and message.
     */
    public function deleteCampaign(int $id): array
    {
        $this->db->transStart();

        if (!$this->campaignModel->find($id)) {
            return ['success' => false, 'message' => 'Campaign template not found.'];
        }

        if (!$this->campaignModel->delete($id)) {
            $this->db->transRollback();
            return ['success' => false, 'message' => 'Failed to delete the campaign template.'];
        }

        $this->db->transComplete();
        return ['success' => true, 'message' => 'Campaign template deleted successfully.'];
    }

    /**
     * Creates a new campaign record and initiates it for sending.
     *
     * @param array $data Input data from the controller.
     * @return array Success/Error status and message.
     */
    public function createAndInitiate(array $data): array
    {
        $this->db->transStart();

        $campaignData = [
            'subject'       => $data['subject'],
            'body'          => $this->_prepareMessage($data['message']),
            'status'          => 'draft', // Will be pending after initiation
            'stop_at_count'   => (int)($data['stop_at_count'] ?? 1000) ?: 1000,
            'quota_increment' => (int)($data['stop_at_count'] ?? 1000) ?: 1000
        ];

        $executionId = $this->campaignModel->insert($campaignData);

        if (!$executionId) {
            $this->db->transRollback();
            return ['success' => false, 'message' => 'Failed to create campaign record.'];
        }

        $initResult = $this->initiateCampaign((int)$executionId);

        if (!$initResult['success']) {
            $this->db->transRollback();
            return $initResult;
        }

        $this->db->transComplete();

        return [
            'success'     => true,
            'message'     => 'Campaign initiated.',
            'executionId' => $executionId
        ];
    }

    /**
     * Initiates a campaign for processing.
     * Snapshots the current user list to avoid "moving goalpost" issues.
     *
     * @param int $campaignId The campaign ID.
     * @return array Success/Error status and message.
     */
    public function initiateCampaign(int $campaignId): array
    {
        $campaign = $this->campaignModel->find($campaignId);

        if (!$campaign) {
            return ['success' => false, 'message' => 'Campaign not found.'];
        }

        if ($campaign->status === 'completed') {
            return ['success' => false, 'message' => 'Campaign is already completed.'];
        }

        // Snapshot the current max user ID
        $maxUser = $this->userModel->selectMax('id')->first();
        $maxUserId = $maxUser ? (int)$maxUser->id : 0;

        // Count total recipients up to that snapshot
        $totalRecipients = $this->userModel->where('id <=', $maxUserId)->countAllResults();

        // Update campaign status
        $this->campaignModel->update($campaignId, [
            'status'            => 'pending',
            'total_recipients'  => $totalRecipients,
            'max_user_id'       => $maxUserId,
            'last_processed_id' => 0,
            'sent_count'        => 0,
            'error_count'       => 0,
        ]);

        return [
            'success'          => true,
            'message'          => 'Campaign initiated successfully.',
            'total_recipients' => $totalRecipients
        ];
    }

    /**
     * Processes a batch of emails for the campaign.
     *
     * @param int $campaignId The campaign ID.
     * @param int $batchSize Number of emails to send in this batch.
     * @return array Batch results and status.
     */
    public function processBatch(int $campaignId, int $batchSize = 50): array
    {
        $campaign = $this->campaignModel->find($campaignId);

        if (!$campaign) {
            return ['status' => 'error', 'message' => 'Campaign not found.'];
        }

        if (!in_array($campaign->status, ['pending', 'sending'])) {
            return ['status' => $campaign->status, 'message' => 'Campaign is not in a processable state.'];
        }

        if ($campaign->status === 'pending') {
            $this->campaignModel->update($campaignId, ['status' => 'sending']);
        }

        $actualBatchSize = $this->_calculateBatchSize($campaign, $batchSize);

        if ($actualBatchSize <= 0 && $campaign->stop_at_count > 0) {
            return [
                'status'       => 'paused',
                'progress'     => min(100, round((($campaign->sent_count + $campaign->error_count) / ($campaign->total_recipients ?: 1)) * 100, 2)),
                'quota_hit_at' => $campaign->quota_hit_at
            ];
        }

        $users = $this->userModel->where('id >', $campaign->last_processed_id)
            ->where('id <=', (int)$campaign->max_user_id)
            ->orderBy('id', 'ASC')
            ->findAll($actualBatchSize);

        if (empty($users)) {
            $this->campaignModel->update($campaignId, ['status' => 'completed']);
            return ['status' => 'completed', 'progress' => 100];
        }

        $emailResults = $this->_sendBatch($campaign, $users);

        $this->db->transStart();

        $newSentCount  = $campaign->sent_count + $emailResults['sent'];
        $newErrorCount = $campaign->error_count + $emailResults['errors'];

        $updateData = [
            'last_processed_id' => $emailResults['last_id'],
            'sent_count'        => $newSentCount,
            'error_count'       => $newErrorCount,
        ];

        if ($this->_isQuotaHit($campaign, $newSentCount)) {
            $updateData['quota_hit_at'] = $campaign->quota_hit_at ?: date('Y-m-d H:i:s');
        }

        if (($newSentCount + $newErrorCount) >= $campaign->total_recipients) {
            $updateData['status'] = 'completed';
        } else if (isset($updateData['quota_hit_at']) && !isset($updateData['status'])) {
            $updateData['status'] = 'paused';
        }

        $this->campaignModel->update($campaignId, $updateData);
        $this->db->transComplete();

        $total = $campaign->total_recipients > 0 ? $campaign->total_recipients : 1;
        $progress = min(100, round((($newSentCount + $newErrorCount) / $total) * 100, 2));

        return [
            'status'            => $updateData['status'] ?? 'sending',
            'processed_count'   => count($users),
            'total_sent'        => $newSentCount,
            'total_errors'      => $newErrorCount,
            'progress'          => $progress,
            'quota_hit_at'      => $updateData['quota_hit_at'] ?? $campaign->quota_hit_at,
        ];
    }

    /**
     * Retries failed emails from the logs.
     *
     * @param int $campaignId The campaign ID.
     * @param int $batchSize Number of emails to retry.
     * @return array Retry results and status.
     */
    public function processRetryBatch(int $campaignId, int $batchSize = 50): array
    {
        $campaign = $this->campaignModel->find($campaignId);

        if (!$campaign) {
            return ['status' => 'error', 'message' => 'Campaign not found.'];
        }

        $actualBatchSize = $this->_calculateBatchSize($campaign, $batchSize);

        if ($actualBatchSize <= 0 && $campaign->stop_at_count > 0) {
            return [
                'status'       => 'paused',
                'progress'     => 100,
                'quota_hit_at' => $campaign->quota_hit_at
            ];
        }

        $failures = $this->db->table('campaign_logs')
            ->where('campaign_id', $campaignId)
            ->where('status', 'failed')
            ->limit($actualBatchSize)
            ->get()->getResult();

        if (empty($failures)) {
            $this->campaignModel->update($campaignId, ['status' => 'completed']);
            return ['status' => 'completed', 'message' => 'All failures retried.', 'progress' => 100];
        }

        $userids = array_column($failures, 'user_id');
        $users = $this->userModel->whereIn('id', $userids)->findAll();

        $emailResults = $this->_sendBatch($campaign, $users);

        $this->db->transStart();

        $newSentCount = $campaign->sent_count + $emailResults['sent'];
        $newErrorCount = max(0, $campaign->error_count - $emailResults['sent']);

        $updateData = [
            'sent_count'  => $newSentCount,
            'error_count' => $newErrorCount,
        ];

        if ($this->_isQuotaHit($campaign, $newSentCount)) {
            $updateData['status'] = 'paused';
            $updateData['quota_hit_at'] = $campaign->quota_hit_at ?: date('Y-m-d H:i:s');
        }

        $this->campaignModel->update($campaignId, $updateData);

        // Cleanup logs for successful retries
        foreach ($failures as $f) {
            $this->db->table('campaign_logs')->delete(['id' => $f->id]);
        }

        $this->db->transComplete();

        return [
            'status'            => $updateData['status'] ?? 'retry_mode',
            'processed_count'   => count($failures),
            'total_sent'        => $newSentCount,
            'total_errors'      => $newErrorCount,
            'progress'          => 100,
            'quota_hit_at'      => $updateData['quota_hit_at'] ?? $campaign->quota_hit_at,
        ];
    }

    /**
     * Pauses an active campaign.
     *
     * @param int $id The campaign ID.
     * @return array Success outcome.
     */
    public function pauseCampaign(int $id): array
    {
        $this->db->transStart();
        $this->campaignModel->update($id, ['status' => 'paused']);
        $this->db->transComplete();

        return ['status' => 'success', 'message' => 'Campaign paused.'];
    }

    /**
     * Resumes a campaign, resetting quota if needed.
     *
     * @param int $campaignId The campaign ID.
     * @return array Success/Error status and message.
     */
    public function resumeCampaign(int $campaignId): array
    {
        $campaign = $this->campaignModel->find($campaignId);

        if (!$campaign) {
            return ['status' => 'error', 'message' => 'Campaign not found.'];
        }

        $this->db->transStart();

        $isNormalDone = ($campaign->sent_count + $campaign->error_count) >= $campaign->total_recipients;

        $updateData = [
            'status' => $isNormalDone ? 'retry_mode' : 'sending'
        ];

        if ($campaign->quota_hit_at) {
            $updateData['quota_hit_at'] = null;

            // Top-Up logic: If quota was hit, increment stop_at_count relative to current sent_count
            $currentSent = (int)$campaign->sent_count;
            $increment   = (int)($campaign->quota_increment ?: 1000);
            $updateData['stop_at_count'] = $currentSent + $increment;
        }

        $this->campaignModel->update($campaignId, $updateData);
        $this->db->transComplete();

        return ['status' => 'success', 'message' => 'Campaign resumed.'];
    }

    /**
     * Initiates retry mode for a campaign.
     *
     * @param int $id The campaign ID.
     * @return array Success outcome.
     */
    public function startRetry(int $id): array
    {
        $this->db->transStart();
        $this->campaignModel->update($id, ['status' => 'retry_mode']);
        $this->db->transComplete();

        return ['success' => true, 'message' => 'Retry process initiated.'];
    }
}
