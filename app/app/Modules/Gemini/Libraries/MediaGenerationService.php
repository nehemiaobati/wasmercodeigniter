<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use App\Models\UserModel;
use CodeIgniter\I18n\Time;

/**
 * Service for generating media (images and videos) using Google's Generative AI models.
 *
 * This service handles:
 * - Configuration of supported media models (Imagen, Veo).
 * - Cost estimation and user balance verification.
 * - Payload construction via ModelPayloadService.
 * - Execution of API requests for media generation.
 * - Handling of asynchronous video generation (polling).
 * - Storage of generated media and transaction logging.
 */
class MediaGenerationService
{
    /**
     * Service for constructing model-specific API payloads.
     * @var \App\Modules\Gemini\Libraries\ModelPayloadService
     */
    protected $modelPayloadService;

    /**
     * Model for managing user data and balances.
     * @var UserModel
     */
    protected $userModel;

    /**
     * Database connection instance.
     * @var \CodeIgniter\Database\BaseConnection
     */
    protected $db;

    /**
     * Configuration for supported media generation models.
     *
     * Includes model identifiers, types, estimated costs (USD), and display names.
     *
     * @var array<string, array>
     */
    public const MEDIA_CONFIGS = [
        'imagen-4.0-generate-preview-06-06' => [
            'type' => 'image',
            'cost' => 0.04,
            'name' => 'Imagen 4.0 Preview'
        ],
        'imagen-4.0-ultra-generate-preview-06-06' => [
            'type' => 'image',
            'cost' => 0.06,
            'name' => 'Imagen 4.0 Ultra Preview'
        ],
        'imagen-4.0-ultra-generate-001' => [
            'type' => 'image',
            'cost' => 0.06,
            'name' => 'Imagen 4.0 Ultra'
        ],
        'gemini-3-pro-image-preview' => [
            'type' => 'image_generation_content',
            'cost' => 0.05,
            'name' => 'Gemini 3 Pro (Image & Text)'
        ],
        'gemini-2.5-flash-image' => [
            'type' => 'image_generation_content',
            'cost' => 0.03,
            'name' => 'Gemini 2.5 Flash (Image & Text)'
        ],
        'gemini-2.5-flash-image-preview' => [
            'type' => 'image_generation_content',
            'cost' => 0.03,
            'name' => 'Gemini 2.5 Flash Image Preview'
        ],
        'veo-2.0-generate-001' => [
            'type' => 'video',
            'cost' => 0.10,
            'name' => 'Veo 2.0'
        ]
    ];

    /**
     * Constructor.
     * Initializes dependencies.
     */
    public function __construct()
    {
        $this->modelPayloadService = service('modelPayloadService');
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Generates media (Image or Video) based on the specified model.
     *
     * This method orchestrates the entire generation process:
     * 1. Validates the model ID.
     * 2. Checks if the user has sufficient balance.
     * 3. Constructs the API payload.
     * 4. Executes the API request.
     * 5. Processes the response based on the media type.
     *
     * @param int $userId The ID of the user requesting generation.
     * @param string|array $input The text prompt (string) or multimodal parts (array).
     * @param string $modelId The identifier of the model to use.
     * @return array An associative array containing 'status' and result data or error message.
     */
    public function generateMedia(int $userId, mixed $input, string $modelId): array
    {
        if (!isset(self::MEDIA_CONFIGS[$modelId])) {
            return ['status' => 'error', 'message' => 'Invalid model ID.'];
        }

        $config = self::MEDIA_CONFIGS[$modelId];
        $costUSD = $config['cost'];

        // 1. Check Balance
        $user = $this->userModel->find($userId);

        // Convert cost to KSH (Fixed Rate: 1 USD = 129 KSH)
        $usdToKsh = 129;
        $costKsh = $costUSD * $usdToKsh;

        if (!$user || $user->balance < $costKsh) {
            return ['status' => 'error', 'message' => 'Insufficient credits.'];
        }

        // 2. Prepare Payload
        $apiKey = getenv('GEMINI_API_KEY');

        // Normalize input to parts array
        if (is_string($input)) {
            $parts = [['text' => $input]];
        } else {
            $parts = $input;
        }

        $payloadData = $this->modelPayloadService->getPayloadConfig($modelId, $apiKey, $parts);

        if (!$payloadData) {
            return ['status' => 'error', 'message' => 'Failed to generate payload configuration.'];
        }

        // 3. Execute Request
        $client = \Config\Services::curlrequest();

        try {
            $response = $client->post($payloadData['url'], [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => $payloadData['body'],
                'http_errors' => false
            ]);

            $httpCode = $response->getStatusCode();
            $responseBody = $response->getBody();

            if ($httpCode !== 200) {
                log_message('error', "Gemini Media API Error ({$httpCode}): " . $responseBody);
                $errData = json_decode($responseBody, true);
                $errMsg = $errData['error']['message'] ?? $responseBody;
                return ['status' => 'error', 'message' => 'We encountered an issue while generating your media. Please try again. (API Error: ' . $errMsg . ')'];
            }

            $responseData = json_decode($responseBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'Gemini Media JSON Decode Error: ' . json_last_error_msg());
                return ['status' => 'error', 'message' => 'Failed to decode API response.'];
            }

            // 4. Handle Response based on Type
            if ($config['type'] === 'image') {
                return $this->handleImageResponse($userId, $modelId, $responseData, $costKsh);
            } elseif ($config['type'] === 'video') {
                return $this->handleVideoResponse($userId, $modelId, $responseData, $costKsh);
            } elseif ($config['type'] === 'image_generation_content') {
                return $this->handleImageGenerationContentResponse($userId, $modelId, $responseData, $costKsh);
            }

            return ['status' => 'error', 'message' => 'Unknown media type configuration.'];
        } catch (\Exception $e) {
            log_message('error', 'Gemini Media Exception: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'HTTP Request failed: ' . $e->getMessage()];
        }
    }

    /**
     * Handles the response for 'image_generation_content' type models (e.g., Gemini 3 Pro).
     *
     * Extracts the image data from the 'candidates' structure, saves it to disk,
     * deducts the user's balance, and logs the transaction.
     *
     * @param int $userId
     * @param string $modelId
     * @param array $responseData
     * @param float $cost
     * @return array
     */
    protected function handleImageGenerationContentResponse(int $userId, string $modelId, array $responseData, float $cost): array
    {
        if (isset($responseData['candidates'][0]['content']['parts'])) {
            $parts = $responseData['candidates'][0]['content']['parts'];
            $foundImage = false;
            $fileName = '';

            foreach ($parts as $part) {
                if (isset($part['inlineData']['data'])) {
                    $base64 = $part['inlineData']['data'];
                    $imageData = base64_decode($base64);

                    // Save to disk
                    $fileName = 'gen_' . time() . '_' . bin2hex(random_bytes(8)) . '.jpg';
                    $uploadPath = WRITEPATH . 'uploads/generated/' . $userId . '/';

                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }

                    if (file_put_contents($uploadPath . $fileName, $imageData) === false) {
                        log_message('error', "Failed to write generated image to: " . $uploadPath . $fileName);
                        continue;
                    }
                    $foundImage = true;
                    break; // Process only the first image
                }
            }

            if ($foundImage) {
                $this->deductCredits($userId, $cost);

                $this->db->table('generated_media')->insert([
                    'user_id' => $userId,
                    'type' => 'image',
                    'model_id' => $modelId,
                    'local_path' => $fileName,
                    'status' => 'completed',
                    'cost' => $cost,
                    'created_at' => Time::now()->toDateTimeString(),
                    'updated_at' => Time::now()->toDateTimeString(),
                ]);

                return [
                    'status' => 'success',
                    'type' => 'image',
                    'url' => site_url('gemini/media/serve/' . $fileName)
                ];
            }
        }

        return ['status' => 'error', 'message' => 'No image data found in the response.'];
    }

    /**
     * Handles the response for standard 'image' type models (e.g., Imagen).
     *
     * Extracts the image data from the 'predictions' structure, saves it to disk,
     * deducts the user's balance, and logs the transaction.
     *
     * @param int $userId
     * @param string $modelId
     * @param array $responseData
     * @param float $cost
     * @return array
     */
    protected function handleImageResponse(int $userId, string $modelId, array $responseData, float $cost): array
    {
        if (isset($responseData['predictions'][0]['bytesBase64Encoded'])) {
            $base64 = $responseData['predictions'][0]['bytesBase64Encoded'];
            $imageData = base64_decode($base64);

            // Save to disk
            $fileName = 'gen_' . time() . '_' . bin2hex(random_bytes(8)) . '.jpg';
            $uploadPath = WRITEPATH . 'uploads/generated/' . $userId . '/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            if (file_put_contents($uploadPath . $fileName, $imageData) === false) {
                log_message('error', "Failed to write generated image to: " . $uploadPath . $fileName);
                return ['status' => 'error', 'message' => 'Failed to save generated image.'];
            }

            $this->deductCredits($userId, $cost);

            $this->db->table('generated_media')->insert([
                'user_id' => $userId,
                'type' => 'image',
                'model_id' => $modelId,
                'local_path' => $fileName,
                'status' => 'completed',
                'cost' => $cost,
                'created_at' => Time::now()->toDateTimeString(),
                'updated_at' => Time::now()->toDateTimeString(),
            ]);

            return [
                'status' => 'success',
                'type' => 'image',
                'url' => site_url('gemini/media/serve/' . $fileName)
            ];
        }

        return ['status' => 'error', 'message' => 'No image data found in the response.'];
    }

    /**
     * Handles the response for 'video' type models (e.g., Veo).
     *
     * Video generation is asynchronous. This method extracts the operation ID,
     * deducts the cost (upfront), creates a pending database record, and returns
     * the operation ID for polling.
     *
     * @param int $userId
     * @param string $modelId
     * @param array $responseData
     * @param float $cost
     * @return array
     */
    protected function handleVideoResponse(int $userId, string $modelId, array $responseData, float $cost): array
    {
        if (isset($responseData['name'])) {
            $opName = $responseData['name']; // Format: "projects/.../operations/..."

            // Deduct Balance on initiation to prevent abuse
            $this->deductCredits($userId, $cost);

            $this->db->table('generated_media')->insert([
                'user_id' => $userId,
                'type' => 'video',
                'model_id' => $modelId,
                'remote_op_id' => $opName,
                'status' => 'pending',
                'cost' => $cost,
                'created_at' => Time::now()->toDateTimeString(),
                'updated_at' => Time::now()->toDateTimeString(),
            ]);

            return [
                'status' => 'pending',
                'type' => 'video',
                'op_id' => $opName
            ];
        }

        return ['status' => 'error', 'message' => 'No operation ID returned for video generation.'];
    }

    /**
     * Polls the status of a long-running video generation operation.
     *
     * If the video is ready, it downloads the content, saves it to disk,
     * and updates the database record status to 'completed'.
     *
     * @param string $opId The operation ID to poll.
     * @return array Status and result URL if completed.
     */
    public function pollVideoStatus(string $opId): array
    {
        $apiKey = getenv('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/{$opId}?key=" . urlencode($apiKey);

        $client = \Config\Services::curlrequest();

        try {
            $response = $client->get($url, ['http_errors' => false]);
            $responseBody = $response->getBody();
            $data = json_decode($responseBody, true);

            if (isset($data['done']) && $data['done'] === true) {
                // Video generation is complete
                if (isset($data['response']['generatedSamples'][0]['video']['uri'])) {
                    $videoUri = $data['response']['generatedSamples'][0]['video']['uri'];

                    // Download Video Content
                    $downloadUrl = $videoUri . '&key=' . urlencode($apiKey);
                    $videoResponse = $client->get($downloadUrl, ['http_errors' => false]);

                    if ($videoResponse->getStatusCode() === 200) {
                        $videoContent = $videoResponse->getBody();

                        // Retrieve the pending record
                        $record = $this->db->table('generated_media')->where('remote_op_id', $opId)->get()->getRow();

                        if ($record) {
                            $fileName = 'vid_' . time() . '_' . bin2hex(random_bytes(8)) . '.mp4';
                            $uploadPath = WRITEPATH . 'uploads/generated/' . $record->user_id . '/';

                            if (!is_dir($uploadPath)) {
                                mkdir($uploadPath, 0755, true);
                            }

                            if (file_put_contents($uploadPath . $fileName, $videoContent) === false) {
                                log_message('error', "Failed to write generated video to: " . $uploadPath . $fileName);
                                return ['status' => 'failed', 'message' => 'Failed to save video file.'];
                            }

                            // Update DB Record
                            $this->db->table('generated_media')->where('id', $record->id)->update([
                                'status' => 'completed',
                                'local_path' => $fileName,
                                'updated_at' => Time::now()->toDateTimeString(),
                            ]);

                            return [
                                'status' => 'completed',
                                'url' => site_url('gemini/media/serve/' . $fileName)
                            ];
                        }
                    }
                } else {
                    return ['status' => 'failed', 'message' => 'Generation failed or no video URI found.'];
                }
            }

            return ['status' => 'pending'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Polling failed: ' . $e->getMessage()];
        }
    }

    /**
     * Deducts credits from the user's balance.
     *
     * @param int $userId
     * @param float $amount
     */
    protected function deductCredits(int $userId, float $amount): void
    {
        // Format as string to ensure precision and match UserModel requirement
        $formattedAmount = number_format($amount, 4, '.', '');
        $this->userModel->deductBalance($userId, $formattedAmount);
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
