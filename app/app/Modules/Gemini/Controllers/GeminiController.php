<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Controllers;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Modules\Gemini\Libraries\GeminiService;
use App\Modules\Gemini\Libraries\MemoryService;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use App\Modules\Gemini\Libraries\DocumentService;
use App\Modules\Gemini\Libraries\MediaGenerationService;
use CodeIgniter\I18n\Time;
use Parsedown;

/**
 * Handles all Gemini AI interactions.
 *
 * Orchestrates user flows for content generation:
 * - Validates inputs and manages file uploads.
 * - Coordinates conversational memory and assistant context.
 * - Manages financial transactions including cost estimation and balance deduction.
 * - Interfaces with GeminiService for multimodal and text generation.
 * 
 * @property IncomingRequest $request
 */
class GeminiController extends BaseController
{
    /**
     * Initializes the controller with its dependencies.
     *
     * @param UserModel|null $userModel Data access layer for user accounts.
     * @param GeminiService|null $geminiService Core AI orchestration service.
     */
    public function __construct(
        protected ?UserModel $userModel = null,
        protected ?GeminiService $geminiService = null
    ) {
        $this->userModel = $userModel ?? new UserModel();
        $this->geminiService = $geminiService ?? service('geminiService');
    }


    // --- Helper Methods ---

    /**
     * Validates and prepares generation request data.
     */
    private function _validateGenerationRequest()
    {
        // 1. Validation
        if (!$this->validate(['prompt' => 'max_length[200000]'])) {
            $msg = 'Prompt is too long. Maximum 200,000 characters allowed.';
            return $this->request->getPost('stream_mode')
                ? $this->_sendSSEError($msg)
                : $this->_respondError($msg);
        }

        $inputText = (string) $this->request->getPost('prompt');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');

        // 2. Empty Check
        if (empty(trim($inputText)) && empty($uploadedFileIds)) {
            $msg = 'Please provide a prompt.';
            return ['error' => $msg];
        }

        return [
            'inputText' => $inputText,
            'uploadedFileIds' => $uploadedFileIds
        ];
    }

    /**
     * Returns success response (AJAX JSON or redirect).
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
     */
    private function _setupSSEHeaders(): void
    {
        $this->response->setContentType('text/event-stream');
        $this->response->setHeader('Cache-Control', 'no-cache');
        $this->response->setHeader('Connection', 'keep-alive');
        $this->response->setHeader('X-Accel-Buffering', 'no');
    }

    /**
     * Builds the final response with parsed markdown and optional audio.
     */
    private function _buildGenerationResponse(array $result, int $userId)
    {
        // 1. Process Audio
        $audioUrl = $this->_resolveAudioUrl($result, $userId);

        // 2. Set Flash Message for Cost
        if ($result['costKSH'] > 0) {
            session()->setFlashdata('success', "KSH " . number_format($result['costKSH'], 2) . " deducted.");
        }

        // 3. Parse Markdown & Prepare Content
        $parsedHtml = $this->_parseMarkdown($result['result']);
        $rawResult = $result['result'];

        // Inject Thinking Block if present
        if (!empty($result['thoughts'])) {
            $parsedHtml = $this->_buildThinkingBlockHtml($result['thoughts']) . "\n\n" . $parsedHtml;
            $rawResult = "=== THINKING PROCESS ===\n\n" . $result['thoughts'] . "\n\n=== ANSWER ===\n\n" . $rawResult;
        }

        // 4. Return Appropriate Response Type
        $responseData = [
            'parsedHtml' => $parsedHtml,
            'rawResult'  => $rawResult,
            'audioUrl'   => $audioUrl,
            'metadata'   => $result
        ];

        return $this->request->isAJAX()
            ? $this->_buildAJAXResponse($responseData)
            : $this->_buildStandardResponse($responseData);
    }

    /**
     * Resolves the Audio URL if audio data is present.
     */
    private function _resolveAudioUrl(array $result, int $userId): ?string
    {
        if (empty($result['audioData'])) return null;

        $audioFilename = $this->geminiService->processAudioForServing($result['audioData'], $userId);
        return $audioFilename ? url_to('gemini.serve_audio', $audioFilename) : null;
    }

    /**
     * Parses markdown text safe for display.
     */
    private function _parseMarkdown(string $text): string
    {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        $parsedown->setBreaksEnabled(true);
        return $parsedown->text($text);
    }

    /**
     * Builds JSON response for AJAX requests.
     */
    private function _buildAJAXResponse(array $data): ResponseInterface
    {
        $payload = [
            'status' => 'success',
            'result' => $data['parsedHtml'],
            'raw_result' => $data['rawResult'],
            'flash_html' => view('App\Views\partials\flash_messages'),
            'used_interaction_ids' => $data['metadata']['used_interaction_ids'] ?? [],
            'new_interaction_id' => $data['metadata']['new_interaction_id'] ?? null,
            'timestamp' => $data['metadata']['timestamp'] ?? null,
            'user_input' => ($this->request->getPost('prompt') ?? ''),
            'csrf_token' => csrf_hash()
        ];

        if ($data['audioUrl']) {
            $payload['audio_url'] = $data['audioUrl'];
        }

        return $this->response->setJSON($payload);
    }

    /**
     * Builds Redirect response for standard requests.
     */
    private function _buildStandardResponse(array $data): RedirectResponse
    {
        $redirect = redirect()->back()->withInput()
            ->with('result', $data['parsedHtml'])
            ->with('raw_result', $data['rawResult']);

        if ($data['audioUrl']) {
            $redirect->with('audio_url', $data['audioUrl']);
        }

        return $redirect;
    }

    /**
     * Build HTML for thinking block display
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
     * Renders the public landing page.
     *
     * @return string Validated HTML content.
     */
    public function publicPage(): string
    {
        $data = [
            'pageTitle'       => 'AI Studio | Video, Image & Document Generation',
            'metaDescription' => 'Generate videos, images and text using Gemini. Integrated with M-Pesa, Airtel Money and Card payments. Built for creators.',
            'canonicalUrl'    => url_to('gemini.public'),
            'robotsTag'       => 'index, follow',
            'heroTitle'       => 'Intelligent Content generation & Analysis',
            'heroSubtitle'    => 'Generate videos, images and text using Gemini.'
        ];
        return view('App\Modules\Gemini\Views\gemini\public_page.php', $data);
    }

    /**
     * Renders the main application dashboard.
     *
     * Retrieves user-specific prompts, settings, and media configurations for tab initialization.
     *
     * @return string Main dashboard view.
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
            'currency_symbol'        => 'KSH',
            'exchange_rate'          => 129,
        ];
        $data['robotsTag'] = 'noindex, follow';

        return view('App\Modules\Gemini\Views\gemini\query_form', $data);
    }

    /**
     * Manages asynchronous media uploads for multimodal context.
     *
     * Stores files in temporary storage associated with the user session.
     * Includes CSRF tokens in responses to maintain frontend synchronization.
     *
     * @return ResponseInterface JSON status and file metadata.
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
     * Purges a temporary uploaded file.
     *
     * @return ResponseInterface JSON deletion status.
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
     * Unified entry point for synchronous content generation.
     *
     * Processes multimodal inputs, manages cost deduction, and returns parsed content.
     *
     * @return RedirectResponse|ResponseInterface Web or AJAX response.
     */
    public function generate()
    {
        $userId = (int) session()->get('userId');

        // 1. Validate & Prepare
        $inputs = $this->_validateGenerationRequest();
        if (isset($inputs['error']) || $inputs instanceof ResponseInterface) {
            return $inputs instanceof ResponseInterface ? $inputs : $this->_respondError($inputs['error']);
        }

        // 2. Delegate to Service
        $userSetting = $this->geminiService->getUserSettings($userId);
        $options = [
            'assistant_mode' => $userSetting ? $userSetting->assistant_mode_enabled : true,
            'voice_mode'     => $userSetting ? $userSetting->voice_output_enabled : false,
        ];

        $result = $this->geminiService->processInteraction(
            $userId,
            $inputs['inputText'],
            $inputs['uploadedFileIds'],
            $options
        );

        // Cleanup
        $this->geminiService->cleanupTempFiles($inputs['uploadedFileIds'], $userId);

        if (isset($result['error'])) {
            log_message('error', "[GeminiController] Generation failed for User ID {$userId}: " . $result['error']);
            return $this->_respondError($result['error']);
        }

        // 3. Build Response
        return $this->_buildGenerationResponse($result, $userId);
    }

    /**
     * Handles real-time text generation via Server-Sent Events (SSE).
     *
     * Manages session locking prevention and structured event packets (text, thoughts, close).
     *
     * @return ResponseInterface SSE stream.
     */
    public function stream(): ResponseInterface
    {
        $userId = (int) session()->get('userId');

        // Setup SSE Headers
        $this->_setupSSEHeaders();

        // 1. Validate & Prepare (Manual check since _validateGenerationRequest returns ResponseInterface on error which breaks SSE)
        $inputText = (string) $this->request->getPost('prompt');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');

        if (empty(trim($inputText)) && empty($uploadedFileIds)) {
            $this->_sendSSEError('Please provide a prompt.');
            return $this->response;
        }

        // 2. Prepare Context & Files via Service
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
     * Persists user settings changes.
     *
     * Supports conversational memory, voice output, and streaming toggles.
     *
     * @return ResponseInterface JSON update status.
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
     * Saves a new prompt template for future use.
     *
     * @return ResponseInterface|RedirectResponse
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
     * Removes a saved prompt template.
     *
     * @param int $id Database identifier.
     * @return ResponseInterface|RedirectResponse
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
     * Resets the conversational history and entity memory.
     *
     * @return RedirectResponse
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
     * Retrieves paginated interaction history.
     *
     * @return ResponseInterface JSON history items.
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
     * Purges a specific interaction record.
     *
     * @return ResponseInterface JSON status.
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
     * Transfers generated audio files.
     *
     * Implements an atomic read-and-delete pattern for ephemeral storage compliance.
     *
     * @param string $fileName Resource shard identifier.
     * @return ResponseInterface Streamed binary data.
     * @throws \CodeIgniter\Exceptions\PageNotFoundException
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
            if (!unlink($path)) {
                log_message('error', "[GeminiController] Failed to delete audio file after serve: {$path}");
            }
        }
        return $this->response;
    }

    /**
     * Converts AI output into downloadable document formats.
     *
     * Supports PDF and DOCX via DocumentService orchestration.
     *
     * @return ResponseInterface|RedirectResponse Binary transfer or dynamic redirect.
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
