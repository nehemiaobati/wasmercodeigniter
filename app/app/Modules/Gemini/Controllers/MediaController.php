<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Controllers;

use App\Controllers\BaseController;
use App\Modules\Gemini\Libraries\MediaGenerationService;
use CodeIgniter\API\ResponseTrait;

/**
 * Controller for managing media generation requests (Images and Videos).
 *
 * This controller handles:
 * - Validation of media generation requests.
 * - Orchestration of calls to the MediaGenerationService.
 * - Polling for status updates on asynchronous tasks (e.g., video generation).
 * - Secure serving of generated media files.
 *
 * @property \CodeIgniter\HTTP\IncomingRequest $request
 */
class MediaController extends BaseController
{
    use ResponseTrait;

    /**
     * Service for handling media generation logic.
     * @var MediaGenerationService
     */
    protected $mediaService;

    /**
     * Constructor.
     * Initializes the MediaGenerationService.
     */
    public function __construct()
    {
        $this->mediaService = service('mediaGenerationService');
    }

    /**
     * Handles the request to generate media.
     *
     * Validates the input prompt and model ID, then delegates the generation
     * task to the MediaGenerationService.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response containing the result or error details.
     */
    public function generate()
    {
        $rules = [
            'prompt' => 'required|min_length[3]|max_length[1000]',
            'model_id' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $userId = (int) session()->get('userId');
        $prompt = $this->request->getVar('prompt');
        $modelId = $this->request->getVar('model_id');
        $uploadedFileIds = (array) $this->request->getVar('uploaded_media');

        // Check if we have uploaded files to process
        $input = $prompt;
        if (!empty($uploadedFileIds)) {
            $parts = [['text' => $prompt]];
            $userTempPath = WRITEPATH . 'uploads/gemini_temp/' . $userId . '/';

            foreach ($uploadedFileIds as $fileId) {
                $filePath = $userTempPath . basename($fileId);
                if (file_exists($filePath)) {
                    $mimeType = mime_content_type($filePath);
                    $parts[] = ['inlineData' => [
                        'mimeType' => $mimeType,
                        'data' => base64_encode(file_get_contents($filePath))
                    ]];
                    // Cleanup handled in service or separate job, but for now we leave it or clean up here?
                    // GeminiController cleans up after generation. We should probably do the same.
                    @unlink($filePath);
                }
            }
            $input = $parts;
        }

        // Validate that the requested model ID exists in the configuration
        $configs = MediaGenerationService::MEDIA_CONFIGS;
        if (!array_key_exists($modelId, $configs)) {
            return $this->fail('Invalid model ID selected.');
        }

        try {
            $result = $this->mediaService->generateMedia($userId, $input, $modelId);

            // Append CSRF token to response for frontend refresh
            $result['token'] = csrf_hash();

            return $this->respond($result);
        } catch (\Exception $e) {
            log_message('error', '[MediaController::generate] ' . $e->getMessage());

            // Return error with CSRF token to keep frontend in sync
            return $this->respond([
                'status' => 'error',
                'message' => 'An unexpected error occurred during media generation.',
                'token' => csrf_hash()
            ], 500);
        }
    }

    /**
     * Polls the status of a long-running operation (e.g., video generation).
     *
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response with the current status.
     */
    public function poll()
    {
        $opId = $this->request->getVar('op_id');

        if (!$opId) {
            return $this->fail('Operation ID is required.');
        }

        try {
            $result = $this->mediaService->pollVideoStatus($opId);

            // Append CSRF token
            $result['token'] = csrf_hash();

            return $this->respond($result);
        } catch (\Exception $e) {
            log_message('error', '[MediaController::poll] ' . $e->getMessage());

            return $this->respond([
                'status' => 'error',
                'message' => 'Polling failed due to a server error.',
                'token' => csrf_hash()
            ], 500);
        }
    }

    /**
     * Serves a generated media file securely with serverless compliance.
     *
     * @param string $filename The name of the file to serve.
     * @return void Outputs the file content directly.
     * @throws \CodeIgniter\Exceptions\PageNotFoundException If the file does not exist.
     */
    public function serve($filename)
    {
        $userId = (int) session()->get('userId');
        $path = WRITEPATH . 'uploads/generated/' . $userId . '/' . basename($filename);

        if (!file_exists($path)) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        header('Content-Type: ' . mime_content_type($path));
        header('Content-Length: ' . filesize($path));

        if ($this->request->getGet('download') === '1') {
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        }

        if (readfile($path) !== false) {
            @unlink($path); // SERVERLESS COMPLIANCE: Delete immediately
        }
        exit;
    }
}
