<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\CampaignModel;
use App\Models\UserModel;
use CodeIgniter\Config\Services;

class CampaignService
{
    protected $campaignModel;
    protected $userModel;

    public function __construct()
    {
        $this->campaignModel = new CampaignModel();
        $this->userModel = new UserModel();
    }

    // --- Helper Methods ---

    /**
     * Core sending logic used by both normal and retry batches.
     * Logs failures to campaign_logs table.
     *
     * @param object $campaign
     * @param array $users
     * @return array
     */
    private function _sendBatch(object $campaign, array $users): array
    {
        $emailService = Services::email();
        $db           = \Config\Database::connect();
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
                // Skip logging success rows to keep DB lean and logic clean
            } else {
                $errorCount++;
                $errorMsg = $emailService->printDebugger(['headers']);
                $db->table('campaign_logs')->insert([
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

        return ['sent' => $sentCount, 'errors' => $errorCount, 'last_id' => $lastId];
    }

    /**
     * Calculates the actual batch size based on remaining quota.
     *
     * @param object $campaign
     * @param int $requestedBatchSize
     * @return int
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
     * @param object $campaign
     * @param int $currentSentCount
     * @return bool
     */
    private function _isQuotaHit(object $campaign, int $currentSentCount): bool
    {
        return $campaign->stop_at_count > 0 && $currentSentCount >= $campaign->stop_at_count;
    }

    // --- Public Methods ---

    /**
     * Initiates a campaign for processing.
     * Snapshots the current user list to avoid "moving goalpost" issues.
     *
     * @param int $campaignId
     * @return array
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

        // Snapshot the current max user ID so we don't email users who register mid-campaign
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

        return ['success' => true, 'message' => 'Campaign initiated successfully.', 'total_recipients' => $totalRecipients];
    }

    /**
     * Processes a batch of emails for the campaign.
     *
     * @param int $campaignId
     * @param int $batchSize
     * @return array
     */
    public function processBatch(int $campaignId, int $batchSize = 50): array
    {
        $campaign = $this->campaignModel->find($campaignId);

        if (!$campaign) {
            return ['status' => 'error', 'message' => 'Campaign not found.'];
        }

        // Handle Retry Mode separately if needed, but for now we prioritize normal batching
        if (!in_array($campaign->status, ['pending', 'sending'])) {
            return ['status' => $campaign->status, 'message' => 'Campaign is not in a processable state.'];
        }

        // If it was pending, mark as sending
        if ($campaign->status === 'pending') {
            $this->campaignModel->update($campaignId, ['status' => 'sending']);
        }

        // Fetch batch of users within the snapshot
        $actualBatchSize = $this->_calculateBatchSize($campaign, $batchSize);

        // If quota is already hit, don't even fetch
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

        $newSentCount  = $campaign->sent_count + $emailResults['sent'];
        $newErrorCount = $campaign->error_count + $emailResults['errors'];

        $updateData = [
            'last_processed_id' => $emailResults['last_id'],
            'sent_count'        => $newSentCount,
            'error_count'       => $newErrorCount,
        ];

        // Quota check: HIT THE LIMIT? (Do this regardless of status so it records for SMTP health)
        if ($this->_isQuotaHit($campaign, $newSentCount)) {
            $updateData['quota_hit_at'] = $campaign->quota_hit_at ?: date('Y-m-d H:i:s');
        }

        // Status check: Processed everyone in the snapshot?
        if (($newSentCount + $newErrorCount) >= $campaign->total_recipients) {
            $updateData['status'] = 'completed';
        } else if (isset($updateData['quota_hit_at']) && !isset($updateData['status'])) {
            $updateData['status'] = 'paused';
        }

        $this->campaignModel->update($campaignId, $updateData);

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
     * @param int $campaignId
     * @param int $batchSize
     * @return array
     */
    public function processRetryBatch(int $campaignId, int $batchSize = 50): array
    {
        $db = \Config\Database::connect();
        $campaign = $this->campaignModel->find($campaignId);

        $actualBatchSize = $this->_calculateBatchSize($campaign, $batchSize);

        if ($actualBatchSize <= 0 && $campaign->stop_at_count > 0) {
            return [
                'status'       => 'paused',
                'progress'     => 100,
                'quota_hit_at' => $campaign->quota_hit_at
            ];
        }

        // Fetch failures from logs
        $totalFailuresAtOnce = $db->table('campaign_logs')
            ->where('campaign_id', $campaignId)
            ->where('status', 'failed')
            ->countAllResults();

        $failures = $db->table('campaign_logs')
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

        $newSentCount = $campaign->sent_count + $emailResults['sent'];
        $newErrorCount = max(0, $campaign->error_count - $emailResults['sent']);

        $updateData = [
            'sent_count'  => $newSentCount,
            'error_count' => $newErrorCount,
        ];

        // Quota check for retries
        if ($this->_isQuotaHit($campaign, $newSentCount)) {
            $updateData['status'] = 'paused';
            $updateData['quota_hit_at'] = $campaign->quota_hit_at ?: date('Y-m-d H:i:s');
        }

        $this->campaignModel->update($campaignId, $updateData);

        // Cleanup logs for those we just retried
        foreach ($failures as $f) {
            $db->table('campaign_logs')->delete(['id' => $f->id]);
        }

        // Progress for retry mode: (Total - Remaining) / Total? 
        // Or just return a standard high number to keep the bar full-ish.
        return [
            'status'            => $updateData['status'] ?? 'retry_mode',
            'processed_count'   => count($failures),
            'total_sent'        => $newSentCount,
            'total_errors'      => $newErrorCount,
            'progress'          => 100, // Retries are always at the "end" phase
            'quota_hit_at'      => $updateData['quota_hit_at'] ?? $campaign->quota_hit_at,
        ];
    }

    /**
     * Resumes a campaign, resetting quota if needed and determining the correct state.
     *
     * @param int $campaignId
     * @return array
     */
    public function resumeCampaign(int $campaignId): array
    {
        $campaign = $this->campaignModel->find($campaignId);

        if (!$campaign) {
            return ['success' => false, 'message' => 'Campaign not found.'];
        }

        // Determine if we should go back to sending or retry_mode
        $isNormalDone = ($campaign->sent_count + $campaign->error_count) >= $campaign->total_recipients;

        $updateData = [
            'status' => $isNormalDone ? 'retry_mode' : 'sending'
        ];

        if ($campaign->quota_hit_at) {
            $updateData['quota_hit_at'] = null;
            $updateData['stop_at_count'] = 0;
        }

        $this->campaignModel->update($campaignId, $updateData);

        return ['success' => true, 'message' => 'Campaign resumed.'];
    }
}
