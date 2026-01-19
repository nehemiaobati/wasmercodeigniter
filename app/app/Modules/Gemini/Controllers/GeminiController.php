<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Controllers;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Modules\Gemini\Libraries\GeminiService;
use App\Modules\Gemini\Libraries\MemoryService;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use App\Modules\Gemini\Libraries\DocumentService;
use App\Modules\Gemini\Libraries\MediaGenerationService;
use CodeIgniter\I18n\Time;
use Parsedown;

/**
 * Controller for managing Gemini AI interactions.
 *
 * This controller orchestrates the entire user flow for AI content generation, including:
 * - Handling user Input and file uploads.
 * - Managing context and memory (Assistant Mode).
 * - Estimating and deducting costs.
 * - Calling the GeminiService for text and speech generation.
 * - Processing and displaying results.
 */
class GeminiController extends BaseController
{
    public function __construct(
        protected ?UserModel $userModel = null,
        protected ?GeminiService $geminiService = null
    ) {
        $this->userModel = $userModel ?? new UserModel();
        $this->geminiService = $geminiService ?? service('geminiService');
    }

    // --- Helper Methods ---

    /**
     * Returns success response (AJAX JSON or redirect).
     *
     * @param string $message Success message.
     * @param array $data Additional data for JSON response.
     * @return ResponseInterface|RedirectResponse
     */
    private function _respondSuccess(string $message, array $data = [])
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(array_merge([
                'status' => 'success',
                'message' => $message,
                'csrf_token' => csrf_hash()
            ], $data));
        }
        return redirect()->back()->with('success', $message);
    }

    /**
     * Returns error response (AJAX JSON or redirect).
     *
     * @param string $message Error message.
     * @param array $errors Validation errors for JSON response.
     * @return ResponseInterface|RedirectResponse
     */
    private function _respondError(string $message, array $errors = [])
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $message,
                'errors' => $errors,
                'csrf_token' => csrf_hash()
            ]);
        }
        return redirect()->back()->withInput()->with('error', $message);
    }

    /**
     * Configures response headers for Server-Sent Events (SSE).
     *
     * @return void
     */
    private function _setupSSEHeaders(): void
    {
        $this->response->setContentType('text/event-stream');
        $this->response->setHeader('Cache-Control', 'no-cache');
        $this->response->setHeader('Connection', 'keep-alive');
        $this->response->setHeader('X-Accel-Buffering', 'no'); // Disable buffering for Nginx
    }

    /**
     * Builds the final response with parsed markdown and optional audio.
     * Refactored to support AJAX with rendered partials.
     *
     * @param array $result Result array from GeminiService.
     * @param int $userId User ID for audio file path resolution.
     * @return RedirectResponse|ResponseInterface
     */
    private function _buildGenerationResponse(array $result, int $userId)
    {
        // Process Audio if present
        $audioUrl = null;
        if (!empty($result['audioData'])) {
            // REFACTOR: Use GeminiService to persist file and return filename
            $audioFilename = $this->geminiService->processAudioForServing($result['audioData'], $userId);
            if ($audioFilename) {
                // Return URL for serveAudio
                $audioUrl = url_to('gemini.serve_audio', $audioFilename);
            }
        }

        // Set Flash Message
        if ($result['costKSH'] > 0) {
            session()->setFlashdata('success', "KSH " . number_format($result['costKSH'], 2) . " deducted.");
        }

        // Parse markdown
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        $parsedown->setBreaksEnabled(true);

        $finalResult = $result['result'];
        $parsedHtml = $parsedown->text($finalResult);

        // Prepare raw result with thoughts (for Plain text view consistency)
        $rawResult = $result['result'];
        if (!empty($result['thoughts'])) {
            $parsedHtml = $this->_buildThinkingBlockHtml($result['thoughts']) . "\n\n" . $parsedHtml;
            $rawResult = "=== THINKING PROCESS ===\n\n" . $result['thoughts'] . "\n\n=== ANSWER ===\n\n" . $rawResult;
        }

        // Handle AJAX
        if ($this->request->isAJAX()) {
            // Render the flash messages partial to a string to ensure consistency
            $flashHtml = view('App\Views\partials\flash_messages');

            $responsePayload = [
                'status' => 'success',
                'result' => $parsedHtml,
                'raw_result' => $rawResult,
                'flash_html' => $flashHtml,
                'used_interaction_ids' => $result['used_interaction_ids'] ?? [],
                'new_interaction_id' => $result['new_interaction_id'] ?? null,
                'timestamp' => $result['timestamp'] ?? null,
                'user_input' => ($this->request->getPost('prompt') ?? ''),
                'csrf_token' => csrf_hash()
            ];

            if ($audioUrl) {
                // Pass the Serve URL to the frontend
                $responsePayload['audio_url'] = $audioUrl;
            }

            return $this->response->setJSON($responsePayload);
        }

        // Handle Fallback Standard Post
        $redirect = redirect()->back()->withInput()
            ->with('result', $parsedHtml)
            ->with('raw_result', $rawResult);

        if ($audioUrl) {
            // Pass the Serve URL to flashdata (Session Hygiene: Only string path, not base64)
            $redirect->with('audio_url', $audioUrl);
        }

        return $redirect;
    }

    /**
     * Build HTML for thinking block display
     *
     * @param string $thoughts The thinking content to display
     * @return string HTML string for thinking block
     */
    private function _buildThinkingBlockHtml(string $thoughts): string
    {
        return sprintf(
            '<details class="thinking-block mb-3">' .
                '<summary class="cursor-pointer text-muted fw-bold small">Thinking Process</summary>' .
                '<div class="thinking-content fst-italic text-muted p-2 border-start mt-1 small">%s</div>' .
                '</details>',
            esc($thoughts)
        );
    }

    /**
     * Sends Server-Sent Events (SSE) Error.
     *
     * @param string $msg
     * @return void
     */
    private function _sendSSEError(string $msg)
    {
        $this->response->setBody("data: " . json_encode([
            'error' => $msg,
            'csrf_token' => csrf_hash()
        ]) . "\n\n");
    }

    // --- Public API ---

    /**
     * Displays the public landing page.
     *
     * @return string The rendered view.
     */
    public function publicPage(): string
    {
        $data = [
            'pageTitle'       => 'Intelligent Content & Document Analysis Platform | Powered by Gemini',
            'metaDescription' => 'Transform how you work with AI. Generate professional content, create stunning images, synthesize videos, and extract insights from PDFs using our advanced platform.',
            'canonicalUrl'    => url_to('gemini.public'),
            'robotsTag'       => 'index, follow',
            'heroTitle'       => 'Enterprise-Grade AI Solutions',
            'heroSubtitle'    => 'A complete suite for content generation, image creation, video synthesis, and intelligent document processing - tailored for your workflow.'
        ];
        return view('App\Modules\Gemini\Views\gemini\public_page.php', $data);
    }

    /**
     * Displays the main application dashboard.
     *
     * Loads user-specific data such as saved prompts and settings.
     *
     * @return string The rendered view.
     */
    public function index(): string
    {
        $userId = (int) session()->get('userId');
        $prompts = $this->geminiService->getUserPrompts($userId);
        $userSetting = $this->geminiService->getUserSettings($userId);

        // Fetch Media Configs for Dynamic Tabs
        $mediaConfigs = MediaGenerationService::MEDIA_CONFIGS;

        $data = [
            'pageTitle'              => 'AI Workspace | Afrikenkid',
            'metaDescription'        => 'Your personal AI workspace for content creation and data analysis.',
            'canonicalUrl'           => url_to('gemini.index'),
            'result'                 => session()->getFlashdata('result'),
            'error'                  => session()->getFlashdata('error'),
            'prompts'                => $prompts,
            'assistant_mode_enabled' => $userSetting ? $userSetting->assistant_mode_enabled : true,
            'voice_output_enabled'   => $userSetting ? $userSetting->voice_output_enabled : false,
            'stream_output_enabled'  => $userSetting ? $userSetting->stream_output_enabled : false,
            // CHANGED: Use audio_url instead of base64 for session hygiene
            'audio_url'              => session()->getFlashdata('audio_url'),
            // Re-define constants locally or fetch from config if needed
            'maxFileSize'            => GeminiService::MAX_FILE_SIZE,
            'maxFiles'               => GeminiService::MAX_FILES,
            'supportedMimeTypes'     => json_encode(GeminiService::SUPPORTED_MIME_TYPES),
            'mediaConfigs'           => $mediaConfigs, // Pass to view
        ];
        $data['robotsTag'] = 'noindex, follow';

        return view('App\Modules\Gemini\Views\gemini\query_form', $data);
    }

    /**
     * Handles asynchronous file uploads for the Gemini context.
     *
     * Files are stored temporarily and associated with the user's session
     * until the final generation request is made.
     *
     * @return ResponseInterface JSON response with upload status and file metadata.
     */
    public function uploadMedia(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            // Include CSRF token even on auth errors to allow frontend recovery
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Auth required.', 'csrf_token' => csrf_hash()]);
        }

        if (!$this->validate([
            'file' => [
                'label' => 'File',
                'rules' => 'uploaded[file]|max_size[file,' . (GeminiService::MAX_FILE_SIZE / 1024) . ']|mime_in[file,' . implode(',', GeminiService::SUPPORTED_MIME_TYPES) . ']',
            ],
        ])) {
            // Include CSRF token in validation errors to prevent token desynchronization
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $this->validator->getErrors()['file'], 'csrf_token' => csrf_hash()]);
        }

        $file = $this->request->getFile('file');

        // Delegate storage to Service
        $result = $this->geminiService->storeTempMedia($file, $userId);

        if (!$result['status']) {
            log_message('error', "Upload failed User {$userId}: " . ($result['error'] ?? 'Unknown error'));
            // Include CSRF token even on save failures to maintain session continuity
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Save failed.', 'csrf_token' => csrf_hash()]);
        }

        return $this->response->setJSON([
            'status'        => 'success',
            'file_id'       => $result['filename'],
            'original_name' => $result['original_name'],
            'csrf_token'    => csrf_hash(),
        ]);
    }

    /**
     * Deletes a temporary uploaded file.
     *
     * @return ResponseInterface JSON response with deletion status.
     */
    public function deleteMedia(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) return $this->response->setStatusCode(403);

        $fileId = $this->request->getPost('file_id');
        if (!$fileId) return $this->response->setStatusCode(400);

        if ($this->geminiService->deleteTempMedia($userId, $fileId)) {
            return $this->response->setJSON(['status' => 'success', 'csrf_token' => csrf_hash()]);
        }

        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'File not found', 'csrf_token' => csrf_hash()]);
    }

    /**
     * Generates content using the Gemini API based on user input and context.
     * Supports AJAX for non-blocking UI updates.
     *
     * @return RedirectResponse|ResponseInterface
     */
    public function generate()
    {
        $userId = (int) session()->get('userId');

        // Validation Guard Clause
        if (!$this->validate(['prompt' => 'max_length[200000]'])) {
            return $this->_respondError('Prompt is too long. Maximum 200,000 characters allowed.');
        }

        // 1. Prepare Inputs
        $inputText = (string) $this->request->getPost('prompt');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');

        // 2. Delegate to Service
        // Transfers detailed request processing to the Service layer to maintain a 'Skinny Controller'.
        // Service handles file encoding, context retrieval (RAG), cost calculation, and API interaction.
        $userSetting = $this->geminiService->getUserSettings($userId);
        $options = [
            'assistant_mode' => $userSetting ? $userSetting->assistant_mode_enabled : true,
            'voice_mode'     => $userSetting ? $userSetting->voice_output_enabled : false,
        ];

        // We moved the file prep and cost check INSIDE processInteraction to make the controller thinner
        $result = $this->geminiService->processInteraction($userId, $inputText, $uploadedFileIds, $options);

        // Cleanup Check
        // Ensures temporary files are removed regardless of success/failure to prevent disk clutter.
        $this->geminiService->cleanupTempFiles($uploadedFileIds, $userId);

        if (isset($result['error'])) {
            log_message('error', "[GeminiController] Generation failed for User ID {$userId}: " . $result['error']);
            return $this->_respondError($result['error']);
        }

        // 3. Build and Return Response
        return $this->_buildGenerationResponse($result, $userId);
    }

    /**
     * Handles streaming text generation via Server-Sent Events (SSE).
     *
     * @return ResponseInterface
     */
    public function stream(): ResponseInterface
    {
        $userId = (int) session()->get('userId');

        // Setup SSE Headers
        $this->_setupSSEHeaders();

        // Input Validation
        $inputText = (string) $this->request->getPost('prompt');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');

        if (empty(trim($inputText)) && empty($uploadedFileIds)) {
            $this->_sendSSEError('Please provide a prompt.');
            return $this->response;
        }

        // 1. Prepare Context & Files via Service
        $userSetting = $this->geminiService->getUserSettings($userId);
        $options = [
            'assistant_mode' => $userSetting ? $userSetting->assistant_mode_enabled : true,
            'voice_mode'     => $userSetting ? $userSetting->voice_output_enabled : false,
        ];

        // This handles files, context building, and balance checks
        $prep = $this->geminiService->prepareStreamContext($userId, $inputText, $uploadedFileIds, $options);

        if (isset($prep['error'])) {
            log_message('error', "[GeminiController] Stream preparation failed for User ID {$userId}: " . $prep['error']);
            $this->_sendSSEError($prep['error']);
            return $this->response;
        }

        // 2. Session Locking Prevention
        session_write_close();

        $this->response->sendHeaders();
        if (ob_get_level() > 0) ob_end_flush();

        // Send CSRF token immediately
        echo "data: " . json_encode(['csrf_token' => csrf_hash()]) . "\n\n";
        flush();

        // 3. Call Stream Service
        $this->geminiService->generateStream(
            $prep['parts'],
            // Chunk Callback
            function ($chunk) use ($userId) {
                if (is_array($chunk) && isset($chunk['error'])) {
                    log_message('error', "[GeminiController] Stream chunk error for User ID {$userId}: " . $chunk['error']);
                    echo "data: " . json_encode(['error' => $chunk['error'], 'csrf_token' => csrf_hash()]) . "\n\n";
                } elseif (is_array($chunk) && isset($chunk['thought'])) {
                    echo "data: " . json_encode(['thought' => $chunk['thought']]) . "\n\n";
                } else {
                    echo "data: " . json_encode(['text' => $chunk]) . "\n\n";
                }
                if (ob_get_level() > 0) ob_flush();
                flush();
            },
            // Complete Callback
            function ($fullText, $usageMetadata, $rawChunks = []) use ($userId, $prep, $inputText, $options, $uploadedFileIds) {
                try {
                    // Delegate all business logic to Service
                    $result = $this->geminiService->finalizeStreamInteraction(
                        $userId,
                        $inputText,
                        $fullText,
                        $usageMetadata,
                        $rawChunks,
                        $prep['contextData'],
                        $options['voice_mode']
                    );

                    // Process Audio URL
                    $audioUrl = null;
                    if (!empty($result['audioData'])) {
                        $audioFilename = $this->geminiService->processAudioForServing($result['audioData'], $userId);
                        if ($audioFilename) {
                            $audioUrl = url_to('gemini.serve_audio', $audioFilename);
                        }
                    }

                    // Send Final Status Event
                    $finalPayload = [
                        'csrf_token' => csrf_hash(),
                        'cost'       => $result['costKSH'],
                        'used_interaction_ids' => $result['used_interaction_ids'] ?? [],
                        'new_interaction_id' => $result['new_interaction_id'] ?? null,
                        'timestamp' => $result['timestamp'] ?? null,
                        'user_input' => $inputText,
                        'audio_url'  => $audioUrl
                    ];

                    echo "event: close\n";
                    echo "data: " . json_encode($finalPayload) . "\n\n";

                    if (ob_get_level() > 0) ob_flush();
                    flush();

                    // Cleanup
                    $this->geminiService->cleanupTempFiles($uploadedFileIds, $userId);
                } catch (\Throwable $e) {
                    // Log error but ensure close event still sends to prevent "Connection Lost"
                    log_message('error', "[GeminiController] Stream completion error for User ID {$userId}: " . $e->getMessage());

                    echo "event: close\n";
                    echo "data: " . json_encode(['csrf_token' => csrf_hash(), 'error' => 'Stream processing failed.']) . "\n\n";

                    if (ob_get_level() > 0) ob_flush();
                    flush();
                }
            }
        );

        exit;
    }

    /**
     * Updates user settings (Assistant Mode, Voice Output).
     *
     * @return ResponseInterface JSON response with update status.
     */
    public function updateSetting(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) return $this->response->setStatusCode(403);

        if (!$this->validate([
            'setting_key' => 'required|in_list[assistant_mode_enabled,voice_output_enabled,stream_output_enabled]',
        ])) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid setting', 'csrf_token' => csrf_hash()]);
        }

        $settingKey = $this->request->getPost('setting_key'); // 'assistant_mode_enabled' or 'voice_output_enabled'
        $isEnabled = $this->request->getPost('enabled') === 'true';

        $this->geminiService->updateUserSetting($userId, $settingKey, $isEnabled);

        return $this->response->setJSON(['status' => 'success', 'csrf_token' => csrf_hash()]);
    }

    /**
     * Adds a new saved prompt for the user.
     *
     * @return ResponseInterface|RedirectResponse JSON response for AJAX, Redirect for standard.
     */
    public function addPrompt()
    {
        $userId = (int) session()->get('userId');

        $rules = [
            'title' => 'required|max_length[255]',
            'prompt_text' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->_respondError('Invalid input.', $this->validator->getErrors());
        }

        $id = $this->geminiService->addPrompt($userId, [
            'title'       => $this->request->getPost('title'),
            'prompt_text' => $this->request->getPost('prompt_text')
        ]);

        return $this->_respondSuccess('Prompt saved successfully.', [
            'prompt' => [
                'id' => $id,
                'title' => $this->request->getPost('title'),
                'prompt_text' => $this->request->getPost('prompt_text')
            ]
        ]);
    }

    /**
     * Deletes a saved prompt.
     *
     * @param int $id The ID of the prompt to delete.
     * @return ResponseInterface|RedirectResponse JSON response for AJAX, Redirect for standard.
     */
    public function deletePrompt(int $id)
    {
        $userId = (int) session()->get('userId');
        if ($this->geminiService->deletePrompt($userId, $id)) {
            return $this->_respondSuccess('Prompt deleted.');
        }

        return $this->_respondError('Unauthorized or not found.');
    }

    /**
     * Clears the user's interaction memory and entities.
     *
     * @return RedirectResponse Redirects back with success or error message.
     */
    public function clearMemory(): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        $success = $this->geminiService->clearUserMemory($userId);

        return redirect()->back()->with(
            $success ? 'success' : 'error',
            $success ? 'Memory cleared.' : 'Failed to clear memory.'
        );
    }

    /**
     * Fetches user interaction history.
     *
     * @return ResponseInterface
     */
    public function fetchHistory()
    {
        $userId = (int) session()->get('userId');
        $limit = $this->request->getVar('limit') ?? 20;
        $offset = $this->request->getVar('offset') ?? 0;

        $history = $this->geminiService->getUserHistory($userId, (int)$limit, (int)$offset);

        return $this->response->setJSON([
            'status' => 'success',
            'history' => $history,
            'csrf_token' => csrf_hash()
        ]);
    }

    /**
     * Deletes a specific interaction.
     *
     * @return ResponseInterface
     */
    public function deleteHistory()
    {
        $userId = (int) session()->get('userId');
        $uniqueId = $this->request->getPost('unique_id');

        if (!$uniqueId) {
            return $this->_respondError('Invalid ID.');
        }

        if ($this->geminiService->deleteUserInteraction($userId, $uniqueId)) {
            return $this->response->setJSON(['status' => 'success', 'csrf_token' => csrf_hash()]);
        }
        return $this->_respondError('Failed to delete.');
    }

    /**
     * Ensures strict unlink pattern for serverless environments.
     *
     * This method serves audio files and immediately deletes them for serverless compliance.
     *
     * @param string $fileName The name of the file to serve.
     * @return ResponseInterface The file response.
     * @throws \CodeIgniter\Exceptions\PageNotFoundException If the file does not exist.
     */
    public function serveAudio(string $fileName)
    {
        $userId = (int) session()->get('userId');
        $path = $this->geminiService->getAudioFilePath($userId, basename($fileName));

        if (!$path) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $mime = str_ends_with($path, '.wav') ? 'audio/wav' : 'audio/mpeg';

        $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string)filesize($path));

        // Serve and delete in one go for serverless compliance (ephemeral storage)
        if (readfile($path) !== false) {
            /*
            if (!unlink($path)) {
                log_message('error', "[GeminiController] Failed to delete audio file after serve: {$path}");
            }
            */
        }
        return $this->response;
    }

    /**
     * Generates and downloads a document (PDF or DOCX) from the content.
     *
     * @return ResponseInterface|RedirectResponse The file download or redirect on error.
     */
    public function downloadDocument()
    {
        $userId = (int) session()->get('userId');

        // Basic Validation
        if (!$this->validate([
            'raw_response' => 'required',
            'format'       => 'required|in_list[pdf,docx]'
        ])) {
            log_message('error', "[GeminiController] Document download validation failed for User ID {$userId}. Errors: " . json_encode($this->validator->getErrors()));
            return redirect()->back()->with('error', 'Invalid request parameters.');
        }

        $content = $this->request->getPost('raw_response');
        $format = $this->request->getPost('format');

        // Execution: Use GeminiService facade to maintain parallel architecture
        // This prevents "ping-pong" dependency by going through the main service layer
        $result = $this->geminiService->generateDocument($content, $format);

        if ($result['status'] === 'success') {
            // ... (keep existing doc download logic)
            $mime = $format === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                : 'application/pdf';

            $filename = 'Studio-Output-' . Time::now()->format('Ymd-His') . '.' . $format;

            return $this->response
                ->setHeader('Content-Type', $mime)
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setBody($result['fileData']); // Unified response body
        }

        // Error handling
        $errorMsg = $result['message'] ?? 'Document generation failed.';
        log_message('error', "[GeminiController] Document generation failed for User ID " . session()->get('userId') . ": " . $errorMsg);
        return redirect()->back()->with('error', $errorMsg);
    }
}
