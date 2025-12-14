<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use App\Models\UserModel;
use CodeIgniter\I18n\Time;

/**
 * Service for generating media (images and videos).
 * Focus: Decoupled Parsing and Unified Artifact Persistence.
 */
class MediaGenerationService
{
    protected $modelPayloadService;
    protected $userModel;
    protected $db;

    public const MEDIA_CONFIGS = [
        'imagen-4.0-generate-preview-06-06' => ['type' => 'image', 'cost' => 0.04, 'name' => 'Imagen 4.0 Preview'],
        'imagen-4.0-ultra-generate-preview-06-06' => ['type' => 'image', 'cost' => 0.06, 'name' => 'Imagen 4.0 Ultra Preview'],
        'imagen-4.0-ultra-generate-001' => ['type' => 'image', 'cost' => 0.06, 'name' => 'Imagen 4.0 Ultra'],
        'gemini-3-pro-image-preview' => ['type' => 'image_generation_content', 'cost' => 0.05, 'name' => 'Gemini 3 Pro (Image)'],
        'gemini-2.5-flash-image' => ['type' => 'image_generation_content', 'cost' => 0.03, 'name' => 'Gemini 2.5 Flash (Image)'],
        'veo-2.0-generate-001' => ['type' => 'video', 'cost' => 0.10, 'name' => 'Veo 2.0']
    ];

    public function __construct()
    {
        $this->modelPayloadService = service('modelPayloadService');
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    public function generateMedia(int $userId, mixed $input, string $modelId): array
    {
        if (!isset(self::MEDIA_CONFIGS[$modelId])) return ['status' => 'error', 'message' => 'Invalid model ID.'];

        $config = self::MEDIA_CONFIGS[$modelId];
        $costKsh = $config['cost'] * 129; // 1 USD = 129 KSH
        $parts = is_string($input) ? [['text' => $input]] : $input;
        $apiKey = getenv('GEMINI_API_KEY');

        // 1. Balance Check
        $user = $this->userModel->find($userId);
        if (!$user || $user->balance < $costKsh) return ['status' => 'error', 'message' => 'Insufficient credits.'];

        // 2. Build Payload
        $payloadData = $this->modelPayloadService->getPayloadConfig($modelId, $apiKey, $parts);
        if (!$payloadData) return ['status' => 'error', 'message' => 'Payload config failed.'];

        // 3. Execute Request
        $client = \Config\Services::curlrequest();
        try {
            $response = $client->post($payloadData['url'], [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => $payloadData['body'],
                'http_errors' => false
            ]);

            if ($response->getStatusCode() !== 200) {
                log_message('error', "Gemini Media Error: " . $response->getBody());
                return ['status' => 'error', 'message' => 'Generation failed at provider.'];
            }

            $responseData = json_decode($response->getBody(), true);

            // 4. Parse & Finalize based on Type
            if ($config['type'] === 'video') {
                return $this->_handleVideoRequest($userId, $modelId, $responseData, $costKsh);
            }

            // Parse Image Data
            $parsed = ($config['type'] === 'image_generation_content')
                ? $this->_parseGeminiImageResponse($responseData)
                : $this->_parseImagenResponse($responseData);

            if (!$parsed) return ['status' => 'error', 'message' => 'No image data in response.'];

            // Persist Artifact
            return $this->_finalizeArtifact($userId, 'image', $parsed['data'], $parsed['ext'], $costKsh, $modelId);
        } catch (\Exception $e) {
            log_message('error', 'Media Gen Exception: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'System error during generation.'];
        }
    }

    // --- Unified Artifact Handler ---

    /**
     * Handles file writing, balance deduction, and DB logging in one atomic flow.
     * Compliant with serverless (creates path on fly) and state management.
     */
    private function _finalizeArtifact(int $userId, string $type, string $binaryData, string $ext, float $cost, string $modelId, ?string $remoteOpId = null): array
    {
        $fileName = ($type === 'video' ? 'vid_' : 'gen_') . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $uploadPath = WRITEPATH . 'uploads/generated/' . $userId . '/';

        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

        if (file_put_contents($uploadPath . $fileName, $binaryData) === false) {
            return ['status' => 'error', 'message' => 'Failed to write artifact to disk.'];
        }

        // Deduct Balance
        $this->userModel->deductBalance($userId, number_format($cost, 4, '.', ''));

        // Insert DB Record
        $this->db->table('generated_media')->insert([
            'user_id' => $userId,
            'type' => $type,
            'model_id' => $modelId,
            'local_path' => $fileName,
            'remote_op_id' => $remoteOpId,
            'status' => 'completed', // Videos finalized here are completed
            'cost' => $cost,
            'created_at' => Time::now()->toDateTimeString(),
            'updated_at' => Time::now()->toDateTimeString(),
        ]);

        return [
            'status' => 'success',
            'type' => $type,
            'url' => site_url('gemini/media/serve/' . $fileName)
        ];
    }

    // --- Pure Parsers ---

    private function _parseImagenResponse(array $response): ?array
    {
        if (isset($response['predictions'][0]['bytesBase64Encoded'])) {
            return ['data' => base64_decode($response['predictions'][0]['bytesBase64Encoded']), 'ext' => 'jpg'];
        }
        return null;
    }

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

    private function _handleVideoRequest(int $userId, string $modelId, array $response, float $cost): array
    {
        if (!isset($response['name'])) return ['status' => 'error', 'message' => 'No operation ID returned.'];

        // Deduct upfront for async video
        $this->userModel->deductBalance($userId, number_format($cost, 4, '.', ''));

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

        return ['status' => 'pending', 'type' => 'video', 'op_id' => $response['name']];
    }

    public function pollVideoStatus(string $opId): array
    {
        $apiKey = getenv('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/{$opId}?key=" . urlencode($apiKey);
        $client = \Config\Services::curlrequest();

        try {
            $response = $client->get($url, ['http_errors' => false]);
            $data = json_decode($response->getBody(), true);

            if (isset($data['done']) && $data['done'] === true) {
                if (isset($data['response']['generatedSamples'][0]['video']['uri'])) {
                    // Download Video
                    $videoUri = $data['response']['generatedSamples'][0]['video']['uri'];
                    $dlResp = $client->get($videoUri . '&key=' . urlencode($apiKey), ['http_errors' => false]);

                    if ($dlResp->getStatusCode() === 200) {
                        // Find record to update
                        $record = $this->db->table('generated_media')->where('remote_op_id', $opId)->get()->getRow();
                        if (!$record) return ['status' => 'error', 'message' => 'Record not found.'];

                        // Reuse finalizeArtifact logic, but we need to update existing record, not insert new.
                        // For simplicity in this plan, we'll manually handle the update logic here or adapt finalizeArtifact.
                        // Adapting logic here to stay within strict scope:

                        $fileName = 'vid_' . time() . '_' . bin2hex(random_bytes(4)) . '.mp4';
                        $uploadPath = WRITEPATH . 'uploads/generated/' . $record->user_id . '/';
                        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

                        file_put_contents($uploadPath . $fileName, $dlResp->getBody());

                        $this->db->table('generated_media')->where('id', $record->id)->update([
                            'status' => 'completed',
                            'local_path' => $fileName,
                            'updated_at' => Time::now()->toDateTimeString(),
                        ]);

                        return ['status' => 'completed', 'url' => site_url('gemini/media/serve/' . $fileName)];
                    }
                }
                return ['status' => 'failed', 'message' => 'Generation failed API side.'];
            }
            return ['status' => 'pending'];
        } catch (\Exception $e) {
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
