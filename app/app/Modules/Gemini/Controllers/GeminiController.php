<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Controllers;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Modules\Gemini\Libraries\GeminiService;
use App\Modules\Gemini\Libraries\MemoryService;
use App\Modules\Gemini\Models\EntityModel;
use App\Modules\Gemini\Models\InteractionModel;
use App\Modules\Gemini\Models\PromptModel;
use App\Models\UserModel;
use App\Modules\Gemini\Models\UserSettingsModel;
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
 * - Handling user input and file uploads.
 * - Managing context and memory (Assistant Mode).
 * - Estimating and deducting costs.
 * - Calling the GeminiService for text and speech generation.
 * - Processing and displaying results.
 */
class GeminiController extends BaseController
{
    protected UserModel $userModel;
    protected GeminiService $geminiService;
    protected PromptModel $promptModel;
    protected UserSettingsModel $userSettingsModel;

    private $cachedUserSettings = null;

    private const SUPPORTED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/webp',
        'audio/mpeg',
        'audio/mp3',
        'audio/wav',
        'video/mov',
        'video/mpeg',
        'video/mp4',
        'video/mpg',
        'video/avi',
        'video/wmv',
        'video/mpegps',
        'video/flv',
        'application/pdf',
        'text/plain'
    ];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_FILES = 5;


    public function __construct()
    {
        $this->userModel         = new UserModel();
        $this->geminiService     = service('geminiService');
        $this->promptModel       = new PromptModel();
        $this->userSettingsModel = new UserSettingsModel();
    }

    // --- Core Helper Methods ---

    /**
     * Retrieves user settings with memoization to prevent redundant queries.
     *
     * @param int $userId The user ID.
     * @return object|null UserSetting entity or null.
     */
    private function _getUserSettings(int $userId)
    {
        if ($this->cachedUserSettings === null) {
            $this->cachedUserSettings = $this->userSettingsModel->where('user_id', $userId)->first();
        }
        return $this->cachedUserSettings;
    }

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
                'token' => csrf_hash()
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
                'token' => csrf_hash()
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
        $prompts = $this->promptModel->where('user_id', $userId)->findAll();
        $userSetting = $this->_getUserSettings($userId);

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
            'audio_url'              => session()->getFlashdata('audio_url'),
            'maxFileSize'            => self::MAX_FILE_SIZE,
            'maxFiles'               => self::MAX_FILES,
            'supportedMimeTypes'     => json_encode(self::SUPPORTED_MIME_TYPES),
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
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Auth required.']);
        }

        if (!$this->validate([
            'file' => [
                'label' => 'File',
                'rules' => 'uploaded[file]|max_size[file,' . (self::MAX_FILE_SIZE / 1024) . ']|mime_in[file,' . implode(',', self::SUPPORTED_MIME_TYPES) . ']',
            ],
        ])) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $this->validator->getErrors()['file']]);
        }

        $file = $this->request->getFile('file');
        $userTempPath = WRITEPATH . 'uploads/gemini_temp/' . $userId . '/';

        if (!is_dir($userTempPath)) {
            mkdir($userTempPath, 0755, true);
        }

        $fileName = $file->getRandomName();
        if (!$file->move($userTempPath, $fileName)) {
            log_message('error', "Upload failed User {$userId}: " . $file->getErrorString());
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Save failed.']);
        }

        return $this->response->setJSON([
            'status'        => 'success',
            'file_id'       => $fileName,
            'original_name' => $file->getClientName(),
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

        $filePath = WRITEPATH . 'uploads/gemini_temp/' . $userId . '/' . basename($fileId);

        if (file_exists($filePath) && unlink($filePath)) {
            return $this->response->setJSON(['status' => 'success', 'csrf_token' => csrf_hash()]);
        }

        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'File not found']);
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
        $user = $this->userModel->find($userId);

        if (!$user) {
            return $this->_respondError('User not found.');
        }

        // Input Validation
        if (!$this->validate([
            'prompt' => 'max_length[200000]'
        ])) {
            return $this->_respondError('Prompt is too long. Maximum 200,000 characters allowed.');
        }

        // 1. Prepare Inputs
        $userSetting = $this->_getUserSettings($userId);
        $options = [
            'assistant_mode' => $userSetting ? $userSetting->assistant_mode_enabled : true,
            'voice_mode' => $userSetting ? $userSetting->voice_output_enabled : false,
        ];

        $inputText = (string) $this->request->getPost('prompt');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');

        // Handle File Parts
        $filesResult = $this->_prepareFilesAndContext($uploadedFileIds, $userId);
        if (isset($filesResult['error'])) {
            return $this->_respondError($filesResult['error']);
        }
        $fileParts = $filesResult['parts'];

        // 2. Process Interaction via Service
        $result = $this->geminiService->processInteraction($userId, $inputText, $fileParts, $options);

        $this->_cleanupTempFiles($uploadedFileIds, $userId); // Always cleanup

        if (isset($result['error'])) {
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
        $user = $this->userModel->find($userId);

        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'User not found']);
        }

        // Setup SSE Headers
        $this->_setupSSEHeaders();

        // Input Validation
        $inputText = (string) $this->request->getPost('prompt');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');

        if (empty(trim($inputText)) && empty($uploadedFileIds)) {
            $this->response->setBody("data: " . json_encode(['error' => 'Please provide a prompt.']) . "\n\n");
            return $this->response;
        }

        // 1. Prepare Context & Files
        $userSetting = $this->_getUserSettings($userId);
        $isAssistantMode = $userSetting ? $userSetting->assistant_mode_enabled : true;

        $contextData = $this->_prepareContext($userId, $inputText, $isAssistantMode);

        $filesResult = $this->_prepareFilesAndContext($uploadedFileIds, $userId);
        if (isset($filesResult['error'])) {
            $this->response->setBody("data: " . json_encode(['error' => $filesResult['error']]) . "\n\n");
            return $this->response;
        }

        $parts = $filesResult['parts'];
        if ($contextData['finalPrompt']) {
            array_unshift($parts, ['text' => $contextData['finalPrompt']]);
        }

        // 2. Estimate Cost & Check Balance
        $estimate = $this->geminiService->estimateCost($parts);
        if ($estimate['status'] && $user->balance < $estimate['costKSH']) {
            $this->_cleanupTempFiles($uploadedFileIds, $userId);
            $this->response->setBody("data: " . json_encode(['error' => "Insufficient balance. Estimated: KSH " . number_format($estimate['costKSH'], 2)]) . "\n\n");
            return $this->response;
        }

        $this->response->sendHeaders();
        if (ob_get_level() > 0) ob_end_flush();
        flush();

        // 3. Call Stream Service
        $this->geminiService->generateStream(
            $parts,
            function ($chunk) {
                if (is_array($chunk) && isset($chunk['error'])) {
                    echo "data: " . json_encode(['error' => $chunk['error']]) . "\n\n";
                } else {
                    echo "data: " . json_encode(['text' => $chunk]) . "\n\n";
                }
                if (ob_get_level() > 0) ob_flush();
                flush();
            },
            function ($fullText, $usageMetadata) use ($userId, $contextData, $inputText) {
                // 1. Calculate and Deduct Cost
                // We do this at the end of the stream to ensure accuracy based on actual token usage.
                $costData = ['costKSH' => 0];
                if ($usageMetadata) {
                    $costData = $this->geminiService->calculateCost($usageMetadata);
                    $deduction = number_format($costData['costKSH'], 4, '.', '');
                    $this->userModel->deductBalance($userId, $deduction);
                }

                // 2. Update Context Memory
                // Store the interaction so the Assistant Mode can recall it in future queries.
                if (isset($contextData['memoryService'])) {
                    $contextData['memoryService']->updateMemory($inputText, $fullText, $contextData['usedInteractionIds']);
                }

                // 3. Send Final Status Event
                // We explicitly send 'event: close' separately to ensure the client parser handles it cleanly,
                // even if it arrives in the same TCP packet as the last text chunk.
                echo "event: close\n";
                echo "data: " . json_encode([
                    'csrf_token' => csrf_hash(), // Refresh CSRF for next request
                    'cost' => $costData['costKSH']
                ]) . "\n\n";

                // Force flush the buffer to ensure the client receives the close event immediately.
                if (ob_get_level() > 0) ob_flush();
                flush();
            }
        );

        $this->_cleanupTempFiles($uploadedFileIds, $userId);
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
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid setting']);
        }

        $settingKey = $this->request->getPost('setting_key'); // 'assistant_mode_enabled' or 'voice_output_enabled'
        $isEnabled = $this->request->getPost('enabled') === 'true';

        $setting = $this->userSettingsModel->where('user_id', $userId)->first();

        if ($setting) {
            $this->userSettingsModel->update($setting->id, [$settingKey => $isEnabled]);
        } else {
            $this->userSettingsModel->save([
                'user_id' => $userId,
                $settingKey => $isEnabled
            ]);
        }

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

        $id = $this->promptModel->insert([
            'user_id' => $userId,
            'title' => $this->request->getPost('title'),
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
        $prompt = $this->promptModel->find($id);

        if ($prompt && $prompt->user_id == $userId) {
            $this->promptModel->delete($id);
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
        $success = service('memory', $userId)->clearAll();

        return redirect()->back()->with(
            $success ? 'success' : 'error',
            $success ? 'Memory cleared.' : 'Failed to clear memory.'
        );
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
        $path = WRITEPATH . 'uploads/ttsaudio_secure/' . $userId . '/' . basename($fileName);

        if (!file_exists($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $mime = str_ends_with($path, '.wav') ? 'audio/wav' : 'audio/mpeg';

        $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string)filesize($path));

        // Serve and delete in one go for serverless compliance (ephemeral storage)
        if (readfile($path) !== false) {
            @unlink($path);
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
        // Basic Validation
        if (!$this->validate([
            'raw_response' => 'required',
            'format'       => 'required|in_list[pdf,docx]'
        ])) {
            return redirect()->back()->with('error', 'Invalid request parameters.');
        }

        $content = $this->request->getPost('raw_response');
        $format = $this->request->getPost('format');

        // Execution: Service now guarantees a 'fileData' string on success
        $result = service('documentService')->generate($content, $format);

        if ($result['status'] === 'success') {
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
        return redirect()->back()->with('error', $errorMsg);
    }

    // --- Private Helpers ---

    /**
     * Prepares the context for the AI generation request.
     *
     * If Assistant Mode is enabled, this retrieves relevant past interactions
     * from the MemoryService and constructs a context-aware system prompt.
     *
     * @param int $userId The user ID.
     * @param string $inputText The user's current query.
     * @param bool $isAssistantMode Whether Assistant Mode is enabled.
     * @return array An array containing:
     *               - 'finalPrompt' (string): The constructed prompt with context.
     *               - 'memoryService' (MemoryService|null): The memory service instance.
     *               - 'usedInteractionIds' (array): IDs of interactions used for context.
     */
    private function _prepareContext(int $userId, string $inputText, bool $isAssistantMode): array
    {
        $data = ['finalPrompt' => $inputText, 'memoryService' => null, 'usedInteractionIds' => []];

        if ($isAssistantMode && !empty(trim($inputText))) {
            $memoryService = service('memory', $userId);
            $recalled = $memoryService->getRelevantContext($inputText);

            $template = $memoryService->getTimeAwareSystemPrompt();
            $template = str_replace('{{CURRENT_TIME}}', Time::now()->format('Y-m-d H:i:s T'), $template);
            $template = str_replace('{{CONTEXT_FROM_MEMORY_SERVICE}}', $recalled['context'], $template);
            $template = str_replace('{{USER_QUERY}}', htmlspecialchars($inputText), $template);
            $template = str_replace('{{TONE_INSTRUCTION}}', "Maintain default persona: dry, witty, concise.", $template);

            $data['finalPrompt'] = $template;
            $data['memoryService'] = $memoryService;
            $data['usedInteractionIds'] = $recalled['used_interaction_ids'];
        }
        return $data;
    }

    /**
     * Prepares files and assembles file parts array for API request.
     *
     * @param array $uploadedFileIds Array of uploaded file IDs.
     * @param int $userId User ID for file path resolution.
     * @return array Returns ['error' => string] on failure, or ['parts' => array] on success.
     */
    private function _prepareFilesAndContext(array $uploadedFileIds, int $userId): array
    {
        $uploadResult = $this->_handlePreUploadedFiles($uploadedFileIds, $userId);

        if (isset($uploadResult['error'])) {
            $this->_cleanupTempFiles($uploadedFileIds, $userId);
            return ['error' => $uploadResult['error']];
        }

        return ['parts' => $uploadResult['parts']];
    }

    /**
     * Processes files that were uploaded asynchronously.
     *
     * Reads the temporary files, validates their MIME types, and converts them
     * to the base64 format expected by the Gemini API.
     *
     * @param array $fileIds Array of file IDs (filenames) to process.
     * @param int $userId The user ID.
     * @return array An array containing 'parts' (array of API-ready file objects) or 'error' (string).
     */
    private function _handlePreUploadedFiles(array $fileIds, int $userId): array
    {
        $parts = [];
        $userTempPath = WRITEPATH . 'uploads/gemini_temp/' . $userId . '/';

        foreach ($fileIds as $fileId) {
            $filePath = $userTempPath . basename($fileId);

            if (!file_exists($filePath)) {
                return ['error' => "File not found. Please upload again."];
            }

            $mimeType = mime_content_type($filePath);
            if (!in_array($mimeType, self::SUPPORTED_MIME_TYPES, true)) {
                return ['error' => "Unsupported file type."];
            }

            $parts[] = ['inlineData' => [
                'mimeType' => $mimeType,
                'data' => base64_encode(file_get_contents($filePath))
            ]];
        }
        return ['parts' => $parts];
    }

    /**
     * Cleans up temporary files after processing.
     *
     * @param array $fileIds Array of file IDs to delete.
     * @param int $userId The user ID.
     */
    private function _cleanupTempFiles(array $fileIds, int $userId): void
    {
        foreach ($fileIds as $fileId) {
            @unlink(WRITEPATH . 'uploads/gemini_temp/' . $userId . '/' . basename($fileId));
        }
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
            // Returns filename only, e.g. "speech_123.mp3"
            $audioFilename = $this->_processAudioData($result['audioData']);
            if ($audioFilename) {
                // Construct full URL for Frontend
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
        $parsedHtml = $parsedown->text($result['result']);

        // Handle AJAX
        if ($this->request->isAJAX()) {
            // Render the flash messages partial to a string to ensure consistency
            $flashHtml = view('App\Views\partials\flash_messages');

            $responsePayload = [
                'status' => 'success',
                'result' => $parsedHtml,
                'raw_result' => $result['result'],
                'flash_html' => $flashHtml,
                'token' => csrf_hash()
            ];

            if ($audioUrl) {
                // Return the generated URL so the frontend can create the <audio> tag
                $responsePayload['audio_url'] = $audioUrl;
            }

            return $this->response->setJSON($responsePayload);
        }

        // Handle Fallback Standard Post
        $redirect = redirect()->back()->withInput()
            ->with('result', $parsedHtml)
            ->with('raw_result', $result['result']);

        if ($audioUrl) {
            // Pass the URL for the view to use in the <audio> tag
            $redirect->with('audio_url', basename($audioUrl)); // passing filename for route consistency in view logic
        }

        // For non-AJAX, we rely on the session flashdata to display the file.
        // In serverless, if the file is deleted after the first read (by serveAudio logic),
        // we might prefer to embed it directly if not using AJAX. 
        // However, the existing view logic handles audio_file_path checks. 
        // For strict serverless compliance, AJAX + Blob or ephemeral URL is preferred.
        // We will stick to the URL pattern which works if served immediately.

        return $redirect;
    }

    /**
     * Handles the storage and conversion of the raw audio data.
     *
     * Delegates to the FFmpegService to convert the raw audio to a browser-compatible format.
     *
     * @param string $base64Data Base64 encoded raw audio data.
     * @return string|null The filename of the processed audio file, or null on failure.
     */
    private function _processAudioData(string $base64Data): ?string
    {
        $userId = (int) session()->get('userId');
        $securePath = WRITEPATH . 'uploads/ttsaudio_secure/' . $userId . '/';

        if (!is_dir($securePath)) mkdir($securePath, 0755, true);

        // Generate base name (e.g., "speech_651a...")
        $filenameBase = 'speech_' . bin2hex(random_bytes(8));

        // Delegate to Service (Returns .mp3 OR .wav)
        $result = service('ffmpegService')->processAudio(
            $base64Data,
            $securePath,
            $filenameBase
        );

        return $result['success'] ? $result['fileName'] : null;
    }
}
