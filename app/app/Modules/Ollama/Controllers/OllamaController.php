<?php

declare(strict_types=1);

namespace App\Modules\Ollama\Controllers;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Modules\Ollama\Libraries\OllamaService;
use App\Modules\Ollama\Models\OllamaPromptModel;
use App\Modules\Ollama\Models\OllamaUserSettingsModel;
use App\Modules\Ollama\Models\OllamaInteractionModel;
use App\Modules\Ollama\Models\OllamaEntityModel;
use App\Modules\Ollama\Libraries\OllamaDocumentService;
use App\Modules\Ollama\Entities\OllamaUserSetting;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use Parsedown;

/**
 * Ollama Controller
 *
 * Handles all HTTP interactions for the Ollama module, which provides
 * local LLM inference capabilities via the Ollama server. Supports text generation,
 * multimodal input (images), conversational memory, and document export.
 *
 * Key Features:
 * - Local AI inference (Llama 3, Mistral, DeepSeek, etc.)
 * - Multimodal support (text + images for vision models)
 * - Hybrid memory system (vector embeddings + keyword search)
 * - Real-time streaming responses (SSE)
 * - Document generation (PDF/DOCX)
 *
 * @package App\Modules\Ollama\Controllers
 */
class OllamaController extends BaseController
{
    /** @var array List of allowed MIME types for image uploads */
    private const SUPPORTED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/webp',
        'image/gif',
    ];

    /** @var int Maximum file size in bytes (10MB) */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /** @var int Maximum number of files per request */
    private const MAX_FILES = 3;

    /** @var float Cost in credits per AI request */
    private const COST_PER_REQUEST = 1.00;

    /**
     * Constructor with Property Promotion (PHP 8.0+)
     *
     * Automatically declares and initializes protected properties from constructor parameters.
     * This eliminates the need for explicit property declarations and assignments in the constructor body.
     *
     * @param UserModel $userModel Handles user authentication and balance management
     * @param OllamaService $ollamaService Core service for Ollama API communication
     * @param OllamaPromptModel $promptModel Manages saved user prompts
     * @param OllamaUserSettingsModel $userSettingsModel Handles user preferences (assistant mode, streaming)
     */
    public function __construct(
        protected UserModel $userModel = new UserModel(),
        protected OllamaService $ollamaService = new OllamaService(),
        protected OllamaPromptModel $promptModel = new OllamaPromptModel(),
        protected OllamaUserSettingsModel $userSettingsModel = new OllamaUserSettingsModel(),
        protected $db = null
    ) {
        $this->db = $db ?? \Config\Database::connect();
    }

    public function index(): string
    {
        $userId = (int) session()->get('userId');
        $prompts = $this->promptModel->where('user_id', $userId)->findAll();
        $userSetting = $this->userSettingsModel->where('user_id', $userId)->first();

        $modelsResponse = $this->ollamaService->getModels();
        $availableModels = ($modelsResponse['status'] === 'success' && !empty($modelsResponse['data']))
            ? $modelsResponse['data']
            : ['llama3'];

        $data = [
            'pageTitle'              => 'Local AI Workspace | Ollama',
            'metaDescription'        => 'Interact with local LLMs via Ollama.',
            'canonicalUrl'           => url_to('ollama.index'),
            'result'                 => session()->getFlashdata('result'),
            'error'                  => session()->getFlashdata('error'),
            'prompts'                => $prompts,
            'assistant_mode_enabled' => $userSetting ? $userSetting->assistant_mode_enabled : true,
            'stream_output_enabled'  => $userSetting ? $userSetting->stream_output_enabled : true,
            'maxFileSize'            => self::MAX_FILE_SIZE,
            'maxFiles'               => self::MAX_FILES,
            'supportedMimeTypes'     => json_encode(self::SUPPORTED_MIME_TYPES),
            'availableModels'        => $availableModels,
            'robotsTag'              => 'noindex, follow'
        ];

        return view('App\Modules\Ollama\Views\ollama\query_form', $data);
    }

    public function uploadMedia(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Auth required.', 'csrf_token' => csrf_hash()]);
        }

        if (!$this->validate([
            'file' => [
                'label' => 'File',
                'rules' => 'uploaded[file]|max_size[file,' . (self::MAX_FILE_SIZE / 1024) . ']|mime_in[file,' . implode(',', self::SUPPORTED_MIME_TYPES) . ']',
            ],
        ])) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $this->validator->getErrors()['file'], 'csrf_token' => csrf_hash()]);
        }

        $file = $this->request->getFile('file');

        // Delegate storage to Service
        $result = $this->ollamaService->storeTempMedia($file, $userId);

        if (!$result['status']) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $result['error'], 'csrf_token' => csrf_hash()]);
        }

        return $this->response->setJSON([
            'status'        => 'success',
            'file_id'       => $result['filename'],
            'original_name' => $result['original_name'],
            'csrf_token'    => csrf_hash(),
        ]);
    }

    public function deleteMedia(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'csrf_token' => csrf_hash()]);

        $fileId = $this->request->getPost('file_id');
        if (!$fileId) return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'csrf_token' => csrf_hash()]);

        $filePath = WRITEPATH . 'uploads/ollama_temp/' . $userId . '/' . basename($fileId);

        if (file_exists($filePath) && unlink($filePath)) {
            return $this->response->setJSON(['status' => 'success', 'csrf_token' => csrf_hash()]);
        }

        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'File not found', 'csrf_token' => csrf_hash()]);
    }

    public function generate(): ResponseInterface
    {
        set_time_limit(300);

        $userId = (int) session()->get('userId');
        $user = $this->userModel->find($userId);

        if (!$user) return redirect()->back()->with('error', 'User not found.');

        if (!$this->validate([
            'prompt' => 'max_length[100000]',
            'model'  => 'required'
        ])) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid input.',
                    'csrf_token' => csrf_hash()
                ]);
            }
            return redirect()->back()->withInput()->with('error', 'Invalid input.');
        }

        $inputText = strip_tags((string) $this->request->getPost('prompt'));
        $selectedModel = (string) $this->request->getPost('model');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');

        $userSetting = $this->userSettingsModel->where('user_id', $userId)->first();
        $isAssistantMode = $userSetting ? $userSetting->assistant_mode_enabled : true;

        if (!$this->_hasBalance($user, self::COST_PER_REQUEST)) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Insufficient balance.',
                    'csrf_token' => csrf_hash()
                ]);
            }
            return redirect()->back()->withInput()->with('error', 'Insufficient balance.');
        }

        $images = $this->_processUploadedFiles($uploadedFileIds, $userId);

        $response = $isAssistantMode
            ? (new \App\Modules\Ollama\Libraries\OllamaMemoryService($userId))->processChat($inputText, $selectedModel, $images)
            : $this->ollamaService->generateChat($selectedModel, $this->_buildMessages($inputText, $images));

        // Handle new standardized return format
        if (isset($response['status']) && $response['status'] === 'error') {
            $msg = $response['message'] ?? 'Unknown error';
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status'     => 'error',
                    'message'    => $msg,
                    'csrf_token' => csrf_hash()
                ]);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        // Legacy error handling for non-standardized responses (e.g., from OllamaMemoryService)
        if (isset($response['error']) || (isset($response['success']) && !$response['success'])) {
            $msg = $response['error'] ?? 'Unknown error';
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status'     => 'error',
                    'message'    => $msg,
                    'csrf_token' => csrf_hash()
                ]);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        // Extract result from new format or legacy format
        $resultText = $response['data']['result'] ?? $response['result'] ?? $response['response'] ?? '';

        $this->userModel->deductBalance((int)$user->id, (string)self::COST_PER_REQUEST);

        $parsedown = new Parsedown();
        $parsedown->setBreaksEnabled(true);
        $parsedown->setSafeMode(true);
        $finalHtml = $parsedown->text($resultText);

        if ($this->request->isAJAX()) {
            session()->setFlashdata('success', 'Generated successfully. Cost: ' . self::COST_PER_REQUEST . ' credits.');
            return $this->response->setJSON([
                'status'     => 'success',
                'result'     => $finalHtml,
                'raw_result' => $resultText,
                'flash_html' => view('App\Views\partials\flash_messages'),
                'csrf_token' => csrf_hash()
            ]);
        }

        return redirect()->back()->withInput()
            ->with('result', $finalHtml)
            ->with('raw_result', $resultText)
            ->with('success', 'Generated successfully. Cost: ' . self::COST_PER_REQUEST . ' credits.');
    }

    /**
     * Handles streaming text generation via Server-Sent Events (SSE).
     * Mirrored strictly from GeminiController::stream.
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

        // Setup SSE Headers (Mirrors Gemini _setupSSEHeaders inline or via method if copied)
        $this->response->setContentType('text/event-stream');
        $this->response->setHeader('Cache-Control', 'no-cache');
        $this->response->setHeader('Connection', 'keep-alive');
        $this->response->setHeader('X-Accel-Buffering', 'no'); // Disable buffering for Nginx

        // Input Validation
        $inputText = (string) $this->request->getPost('prompt');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');
        $selectedModel = (string) $this->request->getPost('model');

        if (empty(trim($inputText)) && empty($uploadedFileIds)) {
            $this->response->setBody("data: " . json_encode([
                'error' => 'Please provide a prompt.',
                'csrf_token' => csrf_hash()
            ]) . "\n\n");
            return $this->response;
        }

        // Check Balance
        if (!$this->_hasBalance($user, self::COST_PER_REQUEST)) {
            $this->response->setBody("data: " . json_encode([
                'error' => "Insufficient balance.",
                'csrf_token' => csrf_hash()
            ]) . "\n\n");
            return $this->response;
        }

        // 1. Prepare Context & Files
        $images = $this->_processUploadedFiles($uploadedFileIds, $userId);

        $userSetting = $this->userSettingsModel->where('user_id', $userId)->first();
        $isAssistantMode = $userSetting ? $userSetting->assistant_mode_enabled : true;

        // Context Construction (Simplified for Ollama compared to Gemini's MemoryService for now, but preserving flow)
        $messages = $isAssistantMode
            ? [['role' => 'system', 'content' => 'You are a helpful AI assistant.']]
            : [];

        $messages[] = $this->_buildUserMessage($inputText, $images);

        // Session Locking Prevention (Crucial mirroring of Gemini)
        session_write_close();

        $this->response->sendHeaders();
        if (ob_get_level() > 0) ob_end_flush();

        // Send CSRF token immediately to ensure client has it even if stream fails later
        echo "data: " . json_encode(['csrf_token' => csrf_hash()]) . "\n\n";
        flush();

        // 3. Call Stream Service
        // Note: We are adapting OllamaService to match GeminiService's signature: (params, chunkCallback, completeCallback)
        $this->ollamaService->generateStream(
            $selectedModel,
            $messages,
            function ($chunk) {
                if (is_array($chunk) && isset($chunk['error'])) {
                    echo "data: " . json_encode([
                        'error' => $chunk['error'],
                        'csrf_token' => csrf_hash()
                    ]) . "\n\n";
                } else {
                    echo "data: " . json_encode(['text' => $chunk]) . "\n\n";
                }
                if (ob_get_level() > 0) ob_flush();
                flush();
            },
            function ($fullText, $usageMetadata) use ($userId, $inputText) {
                // Delegate business logic to Service
                $result = $this->ollamaService->finalizeStreamInteraction($userId, $inputText, $fullText);

                // 3. Send Final Status Event
                $finalPayload = [
                    'csrf_token' => csrf_hash(),
                    'cost'       => $result['cost']
                ];

                echo "event: close\n";
                echo "data: " . json_encode($finalPayload) . "\n\n";

                if (ob_get_level() > 0) ob_flush();
                flush();
            }
        );

        exit;
    }

    public function updateSetting(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        $key = $this->request->getPost('setting_key');
        $enabled = $this->request->getPost('enabled') === 'true';

        if (!in_array($key, ['assistant_mode_enabled', 'stream_output_enabled'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid setting', 'csrf_token' => csrf_hash()]);
        }

        $setting = $this->userSettingsModel->where('user_id', $userId)->first();
        if (!$setting) {
            $setting = new OllamaUserSetting();
            $setting->user_id = $userId;
        }

        $setting->$key = $enabled;
        $this->userSettingsModel->save($setting);

        return $this->response->setJSON(['status' => 'success', 'csrf_token' => csrf_hash()]);
    }

    public function clearMemory(): RedirectResponse
    {
        $userId = (int) session()->get('userId');

        $interactionModel = new OllamaInteractionModel();
        $interactionModel->where('user_id', $userId)->delete();

        $entityModel = new OllamaEntityModel();
        $entityModel->where('user_id', $userId)->delete();

        return redirect()->back()->with('success', 'Memory cleared.');
    }

    public function downloadDocument()
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return $this->response->setStatusCode(403)->setJSON([
                'message' => 'Auth required.',
                'csrf_token' => csrf_hash()
            ]);
        }

        $content = $this->request->getPost('raw_response');
        $format  = $this->request->getPost('format');

        if (empty($content) || !in_array($format, ['pdf', 'docx'])) {
            return $this->response->setStatusCode(400)->setJSON([
                'message' => 'Invalid content or format.',
                'csrf_token' => csrf_hash()
            ]);
        }

        $docService = new OllamaDocumentService();
        $result = $docService->generate($content, $format, [
            'author' => 'Ollama User ' . $userId
        ]);

        if ($result['status'] !== 'success') {
            return $this->response->setStatusCode(500)->setJSON([
                'message' => $result['message'] ?? 'Export failed.',
                'csrf_token' => csrf_hash()
            ]);
        }

        $filename = 'ollama_export_' . date('Ymd_His') . '.' . $format;
        $contentType = ($format === 'pdf') ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

        return $this->response
            ->setHeader('Content-Type', $contentType)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setBody($result['fileData']);
    }

    public function addPrompt(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Auth required', 'csrf_token' => csrf_hash()]);

        $rules = [
            'title'       => 'required|min_length[3]|max_length[255]',
            'prompt_text' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid input', 'csrf_token' => csrf_hash()]);
        }

        $data = [
            'user_id'     => $userId,
            'title'       => $this->request->getPost('title'),
            'prompt_text' => $this->request->getPost('prompt_text'),
        ];

        $id = $this->promptModel->insert($data);

        if ($id) {
            return $this->response->setJSON([
                'status' => 'success',
                'prompt' => array_merge($data, ['id' => $id]),
                'csrf_token' => csrf_hash()
            ]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to save', 'csrf_token' => csrf_hash()]);
    }

    public function deletePrompt($id): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        $prompt = $this->promptModel->find($id);

        if (!$prompt || $prompt->user_id !== $userId) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Unauthorized', 'csrf_token' => csrf_hash()]);
        }

        if ($this->promptModel->delete($id)) {
            return $this->response->setJSON(['status' => 'success', 'csrf_token' => csrf_hash()]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to delete', 'csrf_token' => csrf_hash()]);
    }

    /**
     * Process Uploaded Files (Refactored Helper - DRY Principle)
     *
     * Consolidates file processing logic that was previously duplicated in both
     * generate() and stream() methods. This helper:
     * 1. Verifies file existence
     * 2. Reads and base64-encodes file content for Ollama API
     * 3. Immediately deletes temp files (ephemeral storage pattern)
     *
     * @param array $fileIds Array of file IDs (random filenames) from the upload handler
     * @param int $userId Current user ID for path security/isolation
     * @return array Array of base64-encoded image strings ready for API submission
     */
    private function _processUploadedFiles(array $fileIds, int $userId): array
    {
        $images = [];
        $userTempPath = WRITEPATH . 'uploads/ollama_temp/' . $userId . '/';

        foreach ($fileIds as $fileId) {
            $filePath = $userTempPath . basename($fileId);
            if (file_exists($filePath)) {
                // Encode for Ollama multimodal API
                $images[] = base64_encode(file_get_contents($filePath));
                // Immediate cleanup: files are ephemeral and single-use
                @unlink($filePath);
            }
        }

        return $images;
    }

    /**
     * Check User Balance Sufficiency (Refactored Helper - DRY Principle)
     *
     * Encapsulates balance validation logic that was previously duplicated.
     * Simple comparison but extracted for:
     * - Code clarity and readability
     * - Single source of truth for balance logic
     * - Easier testing and potential future cost complexity
     *
     * @param User $user User entity with balance property
     * @param float $cost Required cost for the operation
     * @return bool True if user has sufficient balance, false otherwise
     */
    private function _hasBalance(User $user, float $cost): bool
    {
        return $user->balance >= $cost;
    }

    /**
     * Build Messages Array for Direct API Mode (Refactored Helper)
     *
     * Constructs a properly formatted messages array for Ollama API when
     * NOT using assistant mode (no system prompt). Used in generate() method.
     *
     * @param string $inputText User's text prompt
     * @param array $images Array of base64-encoded images (empty if text-only)
     * @return array Messages array in Ollama API format
     */
    private function _buildMessages(string $inputText, array $images): array
    {
        $userMessage = ['role' => 'user', 'content' => $inputText];
        if (!empty($images)) {
            $userMessage['images'] = $images;
        }
        return [$userMessage];
    }

    /**
     * Build User Message Object (Refactored Helper)
     *
     * Constructs a single user message object for appending to an existing
     * messages array (e.g., after system prompt in assistant mode). Used in stream() method.
     *
     * @param string $inputText User's text prompt
     * @param array $images Array of base64-encoded images (empty if text-only)
     * @return array Single message object in Ollama API format
     */
    private function _buildUserMessage(string $inputText, array $images): array
    {
        $userMessage = ['role' => 'user', 'content' => $inputText];
        if (!empty($images)) {
            $userMessage['images'] = $images;
        }
        return $userMessage;
    }
}
