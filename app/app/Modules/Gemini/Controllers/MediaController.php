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

    public function __construct(
        protected $mediaService = null
    ) {
        $this->mediaService = $mediaService ?? service('mediaGenerationService');
    }

    /**
     * Handles the request to generate media.
     *
     * Validates the input prompt and model ID, then delegates the generation
     * task to the MediaGenerationService.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response containing the result or error details.
     */
    private function _respondError(string $message, int $code = 400, array $data = [])
    {
        if ($this->request->isAJAX()) {
            return $this->respond(array_merge([
                'status' => 'error',
                'message' => $message,
                'csrf_token' => csrf_hash()
            ], $data), $code);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }

    public function generate()
    {
        $rules = [
            'prompt' => 'required|min_length[3]|max_length[1000]',
            'model_id' => 'required',
        ];

        if (!$this->validate($rules)) {
            // Flatten errors for simple flash message, send full object for AJAX
            $errors = $this->validator->getErrors();
            $msg = implode('. ', $errors);
            return $this->_respondError($msg, 400, ['errors' => $errors]);
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
                    if (!unlink($filePath)) {
                        log_message('error', "[MediaController] Failed to delete temporary uploaded file: {$filePath}");
                    }
                }
            }
            $input = $parts;
        }

        // Validate that the requested model ID exists in the configuration
        $configs = MediaGenerationService::MEDIA_CONFIGS;
        if (!array_key_exists($modelId, $configs)) {
            return $this->_respondError('Invalid model ID selected.');
        }

        try {
            $result = $this->mediaService->generateMedia($userId, $input, $modelId);

            // Handle Concurrency Conflict
            if (isset($result['status']) && $result['status'] === 'conflict') {
                return $this->_respondError($result['message'], 409);
            }

            // General Error from Service (e.g. Provider Error)
            if (isset($result['status']) && $result['status'] === 'error') {
                log_message('error', "[MediaController] Service error for User {$userId}: " . $result['message']);
                return $this->_respondError($result['message']);
            }

            // Set Flash Message
            if (($result['cost_deducted'] ?? 0) > 0) {
                session()->setFlashdata('success', "KSH " . number_format($result['cost_deducted'], 2) . " deducted.");
            }

            // Append CSRF token and Flash HTML to response for frontend refresh
            $result['csrf_token'] = csrf_hash();
            $result['flash_html'] = view('App\Views\partials\flash_messages');

            // Success Response - Handle Redirection for non-AJAX if needed, or consistent JSON
            if ($this->request->isAJAX()) {
                return $this->respond($result);
            }

            // Standard Post Back (e.g. form submission)
            return redirect()->back()->with('success', 'Media generated successfully.');
        } catch (\Exception $e) {
            log_message('error', '[MediaController] Exception: ' . $e->getMessage());
            return $this->_respondError('An unexpected error occurred during media generation.', 500);
        }
    }

    /**
     * Retrieves the currently active video generation job for the user.
     * Used for session persistence / auto-resume.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function active()
    {
        $userId = (int) session()->get('userId');
        try {
            $job = $this->mediaService->getActiveJob($userId);
            return $this->respond([
                'status' => 'success',
                'job' => $job,
                'csrf_token' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to fetch active job.');
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
            $result['csrf_token'] = csrf_hash();

            return $this->respond($result);
        } catch (\Exception $e) {
            log_message('error', '[MediaController] Poll Exception: ' . $e->getMessage());

            return $this->respond([
                'status' => 'error',
                'message' => 'Polling failed due to a server error.',
                'csrf_token' => csrf_hash()
            ], 500);
        }
    }

    /**
     * Serves a generated media file securely with serverless compliance.
     *
     * @param string $filename The name of the file to serve.
     * @return \CodeIgniter\HTTP\ResponseInterface Outputs the file content directly.
     * @throws \CodeIgniter\Exceptions\PageNotFoundException If the file does not exist.
     */
    public function serve($filename)
    {
        $userId = (int) session()->get('userId');
        $path = WRITEPATH . 'uploads/generated/' . $userId . '/' . basename($filename);

        if (!file_exists($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $response = $this->response
            ->setHeader('Content-Type', mime_content_type($path))
            ->setHeader('Content-Length', (string)filesize($path));

        // Use download() if forced or via query param
        if ($this->request->getGet('download') === '1') {
            return $this->response->download($path, null);
        }

        // Otherwise stream for inline viewing
        // We read it into memory because the response object needs body set if not using download()
        // Or we can use the native approach but without exit;
        return $this->response->setBody(file_get_contents($path));
    }
}
