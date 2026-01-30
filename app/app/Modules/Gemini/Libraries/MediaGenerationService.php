<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use App\Models\UserModel;
use CodeIgniter\I18n\Time;

/**
 * Orchestrates multimodal media synthesis (Images and Videos).
 *
 * Implements:
 * - Decoupled parsing for Imagen and Gemini providers.
 * - Unified artifact persistence with serverless compliance.
 * - Atomic financial transactions (Charge-Action-Commit).
 */
class MediaGenerationService
{
    /** @var ModelPayloadService */
    protected $modelPayloadService;

    /** @var UserModel */
    protected $userModel;

    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    /** @var \CodeIgniter\HTTP\CURLRequest */
    protected $curl;

    /**
     * Managed providers and pricing configurations.
     * 
     * Schemes:
     * - 'flat': Deterministic cost per unit.
     * - 'token': Usage-based billing (Input/Output).
     */
    public const MEDIA_CONFIGS = [
        // --- Imagen (Flat Rate) ---
        'imagen-4.0-generate-preview-06-06' => [
            'type' => 'image',
            'pricing_model' => 'flat',
            'cost' => 0.2412,
            'name' => 'Imagen 4.0 Preview'
        ],
        'imagen-4.0-ultra-generate-preview-06-06' => [
            'type' => 'image',
            'pricing_model' => 'flat',
            'cost' => 0.2412,
            'name' => 'Imagen 4.0 Ultra Preview'
        ],
        'imagen-4.0-ultra-generate-001' => [
            'type' => 'image',
            'pricing_model' => 'flat',
            'cost' => 0.2412,
            'name' => 'Imagen 4.0 Ultra'
        ],

        // --- Gemini (Token Based) ---
        // Input: $2.00 / 1M tokens | Output: $120.00 / 1M tokens
        'gemini-3-pro-image-preview' => [
            'type' => 'image_generation_content',
            'pricing_model' => 'token',
            'input_cost_per_1m' => 3.60,
            'output_cost_per_1m' => 216.00,
            'safe_buffer' => 0.36,     // Minimum balance required to attempt ($0.36)
            'name' => 'Gemini 3 Pro (Image)'
        ],

        // Gemini Flash (Token Based)
        'gemini-2.5-flash-image' => [
            'type' => 'image_generation_content',
            'pricing_model' => 'token',
            'input_cost_per_1m' => 0.54,
            'output_cost_per_1m' => 54.00,
            'safe_buffer' => 0.09,
            'name' => 'Gemini 2.5 Flash (Image)'
        ],

        // --- Veo (Flat Rate) ---
        'veo-2.0-generate-001' => [
            'type' => 'video',
            'pricing_model' => 'flat',
            'cost' => 0.18,
            'name' => 'Veo 2.0'
        ],
        'veo-3.1-generate-preview' => [
            'type' => 'video',
            'pricing_model' => 'flat',
            'cost' => 0.27,
            'name' => 'Veo 3.1 Preview'
        ],
        'veo-3.1-fast-generate-preview' => [
            'type' => 'video',
            'pricing_model' => 'flat',
            'cost' => 0.18,
            'name' => 'Veo 3.1 Fast Preview'
        ],
        'veo-3.0-generate-001' => [
            'type' => 'video',
            'pricing_model' => 'flat',
            'cost' => 0.216,
            'name' => 'Veo 3.0'
        ],
        'veo-3.0-fast-generate-001' => [
            'type' => 'video',
            'pricing_model' => 'flat',
            'cost' => 0.144,
            'name' => 'Veo 3.0 Fast'
        ]
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->modelPayloadService = service('modelPayloadService');
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
        $this->curl = \Config\Services::curlrequest();
    }

    // --- Helper Methods ---

    /**
     * Translates token metrics into transaction amounts (KSH).
     * 
     * @param array $usageMetadata Source metrics from API response.
     * @param array $config Provider-specific pricing weights.
     * @return float Total calculated cost.
     */
    private function _calculateTokenCost(array $usageMetadata, array $config): float
    {
        $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
        $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0; // candidatesTokenCount includes generated image tokens

        $inputCostUSD = ($inputTokens / 1000000) * $config['input_cost_per_1m'];
        $outputCostUSD = ($outputTokens / 1000000) * $config['output_cost_per_1m'];

        $totalUSD = $inputCostUSD + $outputCostUSD;

        return $totalUSD * 129; // Convert to KSH
    }

    /**
     * Executes logic within an isolated financial transaction.
     *
     * Workflow: Balance Check -> Deduction -> Closure Execution -> Atomic Commit.
     *
     * @param int $userId System user identifier.
     * @param float $cost Transaction value.
     * @param callable $action Enclosed logic shard.
     * @return array Operation status and metadata.
     */
    private function _executeAtomicTransaction(int $userId, float $cost, callable $action): array
    {
        $this->db->transStart();

        // 1. Deduct Balance
        if (!$this->userModel->deductBalance($userId, number_format($cost, 4, '.', ''), true)) {
            $this->db->transRollback();
            return ['status' => 'error', 'message' => 'Insufficient credits or deduction failed.'];
        }

        // 2. Perform Action (Insert DB, etc)
        try {
            $result = $action();
            if (isset($result['status']) && $result['status'] === 'error') {
                // Logic inside action failed, rollback
                $this->db->transRollback();
                return $result;
            }
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', '[MediaGenerationService] Atomic Action failed: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'System error during transaction.'];
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['status' => 'error', 'message' => 'Transaction failed. Credits not deducted.'];
        }

        return $result;
    }

    /**
     * Manages binary persistence and database registration for generated artifacts.
     *
     * @param int $userId System user identifier.
     * @param string $type Resource type ('image' or 'video').
     * @param string $binaryData Raw data body.
     * @param string $ext Target file extension.
     * @param float $cost Transaction value.
     * @param string $modelId Provider model identifier.
     * @return array Finalization status and URL metadata.
     */
    private function _finalizeArtifact(int $userId, string $type, string $binaryData, string $ext, float $cost, string $modelId): array
    {
        $fileName = ($type === 'video' ? 'vid_' : 'gen_') . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $uploadPath = WRITEPATH . 'uploads/generated/' . $userId . '/';

        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

        // Define the DB Action
        $dbAction = function () use ($userId, $type, $modelId, $fileName, $cost) {
            $this->db->table('generated_media')->insert([
                'user_id' => $userId,
                'type' => $type,
                'model_id' => $modelId,
                'local_path' => $fileName,
                'status' => 'completed',
                'cost' => $cost,
                'created_at' => Time::now()->toDateTimeString(),
                'updated_at' => Time::now()->toDateTimeString(),
            ]);
            return [
                'status' => 'success',
                'type' => $type,
                'url' => site_url('gemini/media/serve/' . $fileName),
                'cost_deducted' => $cost // Info for UI
            ];
        };

        // File Write (Non-Transactional IO)
        if (file_put_contents($uploadPath . $fileName, $binaryData) === false) {
            return ['status' => 'error', 'message' => 'Failed to write artifact to disk.'];
        }

        // Execute Transaction
        $result = $this->_executeAtomicTransaction($userId, $cost, $dbAction);

        // Cleanup if transaction failed
        if ($result['status'] === 'error' && file_exists($uploadPath . $fileName)) {
            @unlink($uploadPath . $fileName);
        }

        return $result;
    }

    /**
     * Manages initial job registration for asynchronous video generation.
     *
     * @param int $userId System user identifier.
     * @param string $modelId Provider model identifier.
     * @param array $response Initial API response structure.
     * @param float $cost Transaction value.
     * @return array Registration status and job metadata.
     */
    private function _handleVideoRequest(int $userId, string $modelId, array $response, float $cost): array
    {
        if (!isset($response['name'])) return ['status' => 'error', 'message' => 'No operation ID returned.'];

        return $this->_executeAtomicTransaction($userId, $cost, function () use ($userId, $modelId, $response, $cost) {
            $this->db->table('generated_media')->insert([
                'user_id' => $userId,
                'type' => 'video',
                'model_id' => $modelId,
                'remote_op_id' => $response['name'],
                'status' => 'pending',
                'cost' => $cost,
                'created_at' => Time::now()->toDateTimeString(),
                'updated_at' => Time::now()->toDateTimeString(),
            ]);
            return ['status' => 'pending', 'type' => 'video', 'op_id' => $response['name'], 'cost_deducted' => $cost];
        });
    }

    /**
     * Parses Imagen response format.
     *
     * @param array $response
     * @return array|null
     */
    private function _parseImagenResponse(array $response): ?array
    {
        if (isset($response['predictions'][0]['bytesBase64Encoded'])) {
            return ['data' => base64_decode($response['predictions'][0]['bytesBase64Encoded']), 'ext' => 'jpg'];
        }
        return null;
    }

    /**
     * Parses Gemini Image response format.
     *
     * @param array $response
     * @return array|null
     */
    private function _parseGeminiImageResponse(array $response): ?array
    {
        $parts = $response['candidates'][0]['content']['parts'] ?? [];
        foreach ($parts as $part) {
            if (isset($part['inlineData']['data'])) {
                return ['data' => base64_decode($part['inlineData']['data']), 'ext' => 'jpg'];
            }
        }
        return null;
    }

    /**
     * Transfers asynchronous video artifacts from provider to local storage.
     *
     * @param string $opId System operation identifier.
     * @param string $videoUri Remote asset location.
     * @param string $apiKey Authentication credential for binary fetch.
     * @return array Download and synchronization status.
     */
    private function _downloadAndSaveVideo(string $opId, string $videoUri, string $apiKey): array
    {
        // Robust URI construction using CI4 URI class
        $uri = new \CodeIgniter\HTTP\URI($videoUri);

        // API Key is strictly required for download, ensure it exists
        // We use addQuery which handles separators (?) and (&) automatically.
        // We first check if 'key' is already present to avoid overwriting (e.g. signed URLs).
        $currentQuery = $uri->getQuery();
        $queryParams = [];
        if (!empty($currentQuery)) {
            parse_str($currentQuery, $queryParams);
        }

        if (!isset($queryParams['key'])) {
            $uri->addQuery('key', $apiKey);
        }

        $dlUrl = (string) $uri;

        $dlResp = $this->curl->get($dlUrl, [
            'http_errors' => false,
            'allow_redirects' => true
        ]);

        if ($dlResp->getStatusCode() !== 200) {
            log_message('error', "[MediaGenerationService] Video download failed for OpID {$opId}. Status: " . $dlResp->getStatusCode() . " | URL: {$dlUrl} | Response: " . substr($dlResp->getBody(), 0, 200));
            $this->_refundFailedJob($opId);
            return ['status' => 'failed', 'message' => "Download failed (HTTP " . $dlResp->getStatusCode() . "). Credits refunded."];
        }

        $record = $this->db->table('generated_media')->where('remote_op_id', $opId)->get()->getRow();
        if (!$record) return ['status' => 'error', 'message' => 'Record not found.'];

        $fileName = 'vid_' . time() . '_' . bin2hex(random_bytes(4)) . '.mp4';
        $uploadPath = WRITEPATH . 'uploads/generated/' . $record->user_id . '/';
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

        // Save File
        file_put_contents($uploadPath . $fileName, $dlResp->getBody());

        // Update DB
        if ($this->db->table('generated_media')->where('id', $record->id)->update([
            'status' => 'completed',
            'local_path' => $fileName,
            'updated_at' => Time::now()->toDateTimeString(),
        ])) {
            return ['status' => 'completed', 'url' => site_url('gemini/media/serve/' . $fileName)];
        }

        // Cleanup if DB update fails
        @unlink($uploadPath . $fileName);
        return ['status' => 'error', 'message' => 'Failed to update record.'];
    }

    /**
     * Reverts financial transactions for failed asynchronous jobs.
     *
     * @param string $opId System operation identifier.
     * @return void
     */
    private function _refundFailedJob(string $opId): void
    {
        $record = $this->db->table('generated_media')->where('remote_op_id', $opId)->get()->getRow();
        if ($record) {
            $this->db->table('generated_media')->where('id', $record->id)->update(['status' => 'failed']);
            $this->userModel->refundBalance((int)$record->user_id, (string)$record->cost);
        }
    }

    // --- Public API ---

    /**
     * Checks if a user has a pending video generation.
     *
     * @param int $userId
     * @return bool
     */
    public function hasPendingVideo(int $userId): bool
    {
        return $this->db->table('generated_media')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where('type', 'video') // Ensure we only block videos
            ->countAllResults() > 0;
    }

    /**
     * Retrieves the most recent active job details.
     *
     * @param int $userId
     * @return array|null
     */
    public function getActiveJob(int $userId): ?array
    {
        $job = $this->db->table('generated_media')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where('type', 'video')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getRowArray();

        if (!$job) return null;

        return [
            'op_id' => $job['remote_op_id'],
            'model_id' => $job['model_id'],
            'created_at' => $job['created_at'],
            // Calculate elapsed time in seconds
            'elapsed' => time() - strtotime($job['created_at'])
        ];
    }

    /**
     * Primary entry point for media generation.
     */
    public function generateMedia(int $userId, mixed $input, string $modelId): array
    {
        if (!isset(self::MEDIA_CONFIGS[$modelId])) return ['status' => 'error', 'message' => 'Invalid model ID.'];

        $config = self::MEDIA_CONFIGS[$modelId];
        $isTokenModel = ($config['pricing_model'] ?? 'flat') === 'token';

        // Determine required balance (Safe Buffer or Exact Cost)
        $requiredBalanceKsh = 0.0;
        if ($isTokenModel) {
            // Use safe buffer for token models (converted to KSH)
            $requiredBalanceKsh = ($config['safe_buffer'] ?? 0.20) * 129;
        } else {
            // Use exact pre-calculated cost for flat models
            $requiredBalanceKsh = $config['cost'] * 129;
        }

        $parts = is_string($input) ? [['text' => $input]] : $input;
        $apiKey = getenv('GEMINI_API_KEY');

        // 0. Concurrency Check (Gatekeeper)
        // Strictly block new VIDEO requests if one is pending.
        if ($config['type'] === 'video' && $this->hasPendingVideo($userId)) {
            // Return specific status for 409 Conflict handling in Controller
            return ['status' => 'conflict', 'message' => 'You have a pending video generation. Please wait for it to complete.'];
        }

        // 1. Balance Check
        $user = $this->userModel->find($userId);
        if (!$user || $user->balance < $requiredBalanceKsh) return ['status' => 'error', 'message' => 'Insufficient credits.'];

        // 2. Build Payload
        $payloadData = $this->modelPayloadService->getPayloadConfig($modelId, $apiKey, $parts);
        if (!$payloadData) return ['status' => 'error', 'message' => 'Payload config failed.'];

        // 3. Execute Request
        try {
            $response = $this->curl->post($payloadData['url'], [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => $payloadData['body'],
                'http_errors' => false
            ]);

            if ($response->getStatusCode() !== 200) {
                $errorData = json_decode($response->getBody(), true);
                $errorMessage = $errorData['error']['message'] ?? $errorData['error']['status'] ?? 'Unknown API error';

                log_message('error', "[MediaGenerationService] Model: {$modelId}. Error: {$errorMessage}. Body: " . $response->getBody());
                return ['status' => 'error', 'message' => "Provider Error: " . $errorMessage];
            }

            $responseData = json_decode($response->getBody(), true);

            // 4. Calculate Final Cost
            $finalCostKsh = 0.0;
            if ($isTokenModel) {
                if (!isset($responseData['usageMetadata'])) {
                    // Fallback if metadata is missing (should not happen for Gemini) - use safe buffer or minimum
                    log_message('warning', "[MediaGenerationService] Missing usageMetadata for token model: {$modelId}");
                    $finalCostKsh = $requiredBalanceKsh;
                } else {
                    $finalCostKsh = $this->_calculateTokenCost($responseData['usageMetadata'], $config);
                }
            } else {
                $finalCostKsh = $requiredBalanceKsh; // Flat rate
            }

            // 5. Parse & Finalize based on Type
            if ($config['type'] === 'video') {
                return $this->_handleVideoRequest($userId, $modelId, $responseData, $finalCostKsh);
            }

            // Parse Image Data
            $parsed = ($config['type'] === 'image_generation_content')
                ? $this->_parseGeminiImageResponse($responseData)
                : $this->_parseImagenResponse($responseData);

            if (!$parsed) return ['status' => 'error', 'message' => 'No image data in response.'];

            // Persist Artifact with Final Cost
            return $this->_finalizeArtifact($userId, 'image', $parsed['data'], $parsed['ext'], $finalCostKsh, $modelId);
        } catch (\Exception $e) {
            log_message('error', "[MediaGenerationService] Model: {$modelId}. Exception: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'System error during generation. No credits deducted.'];
        }
    }

    /**
     * Synchronizes status for long-running generation operations.
     */
    public function pollVideoStatus(string $opId): array
    {
        $apiKey = getenv('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/{$opId}?key=" . urlencode($apiKey);

        try {
            $response = $this->curl->get($url, ['http_errors' => false]);
            $data = json_decode($response->getBody(), true);

            if (isset($data['done']) && $data['done'] === true) {
                // Determine Video URI
                $videoUri = $data['response']['generateVideoResponse']['generatedSamples'][0]['video']['uri']
                    ?? $data['response']['generatedSamples'][0]['video']['uri']
                    ?? null;

                if ($videoUri) {
                    return $this->_downloadAndSaveVideo($opId, $videoUri, $apiKey);
                }

                $this->_refundFailedJob($opId);
                return ['status' => 'failed', 'message' => 'Generation failed API side. Credits refunded.'];
            }
            return ['status' => 'pending'];
        } catch (\Exception $e) {
            log_message('error', "[MediaGenerationService] Poll Error for OpID {$opId}: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Polling error.'];
        }
    }

    /**
     * Retrieves the configuration for all supported media models.
     *
     * @return array
     */
    public function getMediaConfig(): array
    {
        return self::MEDIA_CONFIGS;
    }
}
