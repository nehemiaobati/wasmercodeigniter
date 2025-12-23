<?php

declare(strict_types=1);

namespace App\Modules\Ollama\Controllers;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Modules\Ollama\Libraries\OllamaService;
use App\Modules\Ollama\Libraries\OllamaDocumentService;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Parsedown;

/**
 * Ollama Controller
 *
 * Handles all HTTP interactions for the Ollama module, which provides
 * local LLM inference capabilities via the Ollama server.
 *
 * Architecture:
 * - Skinny Controller: Delegates all business logic to OllamaService.
 * - Handles Request/Response cycle only.
 *
 * @package App\Modules\Ollama\Controllers
 */
class OllamaController extends BaseController
{
    /**
     * Constructor setup
     */
    public function __construct(
        protected ?UserModel $userModel = null,
        protected ?OllamaService $ollamaService = null
    ) {
        $this->userModel = $userModel ?? new UserModel();
        $this->ollamaService = $ollamaService ?? new OllamaService();
    }

    // --- Core Methods ---

    public function index(): string
    {
        $userId = (int) session()->get('userId');

        // Fetch data via Service
        $prompts = $this->ollamaService->getUserPrompts($userId);
        $userSetting = $this->ollamaService->getUserSettings($userId);
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
            'maxFileSize'            => OllamaService::MAX_FILE_SIZE,
            'maxFiles'               => 3,
            'supportedMimeTypes'     => json_encode(OllamaService::SUPPORTED_MIME_TYPES),
            'availableModels'        => $availableModels,
            'robotsTag'              => 'noindex, follow'
        ];

        return view('App\Modules\Ollama\Views\ollama\query_form', $data);
    }

    // --- Media Handling ---

    public function uploadMedia(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Auth required.']);
        }

        if (!$this->validate([
            'file' => [
                'label' => 'File',
                'rules' => 'uploaded[file]|max_size[file,' . (OllamaService::MAX_FILE_SIZE / 1024) . ']|mime_in[file,' . implode(',', OllamaService::SUPPORTED_MIME_TYPES) . ']',
            ],
        ])) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $this->validator->getErrors()['file']]);
        }

        $file = $this->request->getFile('file');
        $result = $this->ollamaService->storeTempMedia($file, $userId);

        if (!$result['status']) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $result['error']]);
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
        if ($userId <= 0) return $this->response->setStatusCode(403);

        $fileId = $this->request->getPost('file_id');
        if (!$fileId) return $this->response->setStatusCode(400);

        $this->ollamaService->cleanupTempFiles([$fileId], $userId);

        return $this->response->setJSON(['status' => 'success', 'csrf_token' => csrf_hash()]);
    }

    // --- Generation ---

    public function generate()
    {
        set_time_limit(300);
        $userId = (int) session()->get('userId');
        $user = $this->userModel->find($userId);

        if (!$user) return $this->_respondError('User not found.');

        // Validation
        if (!$this->validate([
            'prompt' => 'max_length[100000]',
            'model'  => 'required'
        ])) {
            return $this->_respondError('Invalid input.');
        }

        // Prepare Inputs
        $inputText = strip_tags((string) $this->request->getPost('prompt'));
        $selectedModel = (string) $this->request->getPost('model');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');

        $userSetting = $this->ollamaService->getUserSettings($userId);
        $options = [
            'assistant_mode' => $userSetting ? $userSetting->assistant_mode_enabled : true,
        ];

        // Process via Service
        $result = $this->ollamaService->processInteraction($userId, $inputText, $uploadedFileIds, $selectedModel, $options);

        // Cleanup always
        $this->ollamaService->cleanupTempFiles($uploadedFileIds, $userId);

        if (isset($result['status']) && $result['status'] === 'error') {
            return $this->_respondError($result['message']);
        }

        // Success
        return $this->_buildGenerationResponse($result);
    }

    public function stream(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        $user = $this->userModel->find($userId);

        if (!$user) return $this->response->setStatusCode(401)->setJSON(['error' => 'User not found']);

        // SSE Headers
        $this->_setupSSEHeaders();

        $inputText = (string) $this->request->getPost('prompt');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');
        $selectedModel = (string) $this->request->getPost('model');

        if (empty(trim($inputText)) && empty($uploadedFileIds)) {
            $this->_sendSSEError('Please provide a prompt.');
            return $this->response;
        }

        // Check Balance
        if ($user->balance < 1.00) {
            $this->_sendSSEError('Insufficient balance.');
            return $this->response;
        }

        // Context Preparation
        $images = $this->ollamaService->prepareUploadedFiles($uploadedFileIds, $userId);

        $userSetting = $this->ollamaService->getUserSettings($userId);
        $isAssistantMode = $userSetting ? $userSetting->assistant_mode_enabled : true;

        if ($isAssistantMode) {
            $messages = [['role' => 'system', 'content' => 'You are a helpful AI assistant.']];
        } else {
            $messages = [];
        }

        $userMsg = ['role' => 'user', 'content' => $inputText];
        if (!empty($images)) $userMsg['images'] = $images;
        $messages[] = $userMsg;

        session_write_close();
        $this->response->sendHeaders();
        echo "data: " . json_encode(['csrf_token' => csrf_hash()]) . "\n\n";
        flush();

        $this->ollamaService->generateStream(
            $selectedModel,
            $messages,
            function ($chunk) {
                if (is_array($chunk) && isset($chunk['error'])) {
                    echo "data: " . json_encode(['error' => $chunk['error'], 'csrf_token' => csrf_hash()]) . "\n\n";
                } else {
                    echo "data: " . json_encode(['text' => $chunk]) . "\n\n";
                }
                if (ob_get_level() > 0) ob_flush();
                flush();
            },
            function ($fullText, $usage) use ($userId, $inputText, $uploadedFileIds) {
                // Finalize via Service (deduct cost)
                $result = $this->ollamaService->finalizeStreamInteraction($userId, $inputText, $fullText);
                $this->ollamaService->cleanupTempFiles($uploadedFileIds, $userId);

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

    // --- Settings & Prompts ---

    public function updateSetting(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) return $this->response->setStatusCode(403);

        $key = $this->request->getPost('setting_key');
        $enabled = $this->request->getPost('enabled') === 'true';

        if (!in_array($key, ['assistant_mode_enabled', 'stream_output_enabled'])) {
            return $this->response->setStatusCode(400);
        }

        $this->ollamaService->updateUserSetting($userId, $key, $enabled);
        return $this->response->setJSON(['status' => 'success', 'csrf_token' => csrf_hash()]);
    }

    public function addPrompt(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) return $this->response->setStatusCode(403);

        if (!$this->validate(['title' => 'required', 'prompt_text' => 'required'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid input']);
        }

        $id = $this->ollamaService->addPrompt($userId, [
            'title' => $this->request->getPost('title'),
            'prompt_text' => $this->request->getPost('prompt_text')
        ]);

        if ($id) {
            return $this->response->setJSON([
                'status' => 'success',
                'prompt' => ['id' => $id, 'title' => $this->request->getPost('title'), 'prompt_text' => $this->request->getPost('prompt_text')],
                'csrf_token' => csrf_hash()
            ]);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to save']);
    }

    public function deletePrompt($id): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($this->ollamaService->deletePrompt($userId, (int)$id)) {
            return $this->response->setJSON(['status' => 'success', 'csrf_token' => csrf_hash()]);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to delete']);
    }

    public function clearMemory(): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        // Direct model usage for simple clear, or ideally add clearMemory to Service
        (new \App\Modules\Ollama\Models\OllamaInteractionModel())->where('user_id', $userId)->delete();
        (new \App\Modules\Ollama\Models\OllamaEntityModel())->where('user_id', $userId)->delete();

        return redirect()->back()->with('success', 'Memory cleared.');
    }

    public function downloadDocument()
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) return $this->response->setStatusCode(403);

        $content = $this->request->getPost('raw_response');
        $format  = $this->request->getPost('format');

        $docService = new OllamaDocumentService();
        $result = $docService->generate($content, $format, ['author' => 'Ollama User ' . $userId]);

        if ($result['status'] !== 'success') {
            return redirect()->back()->with('error', $result['message']);
        }

        $mime = $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $filename = 'ollama_export_' . date('Ymd_His') . '.' . $format;

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($result['fileData']);
    }

    // --- Helpers ---

    private function _respondSuccess(string $message, array $data = [])
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(array_merge(['status' => 'success', 'message' => $message, 'token' => csrf_hash()], $data));
        }
        return redirect()->back()->with('success', $message);
    }

    private function _respondError(string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => $message, 'token' => csrf_hash()]);
        }
        return redirect()->back()->withInput()->with('error', $message);
    }

    private function _setupSSEHeaders(): void
    {
        $this->response->setContentType('text/event-stream');
        $this->response->setHeader('Cache-Control', 'no-cache');
        $this->response->setHeader('Connection', 'keep-alive');
        $this->response->setHeader('X-Accel-Buffering', 'no');
    }

    private function _sendSSEError(string $msg)
    {
        $this->response->setBody("data: " . json_encode(['error' => $msg, 'csrf_token' => csrf_hash()]) . "\n\n");
    }

    private function _buildGenerationResponse(array $result)
    {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        $parsedown->setBreaksEnabled(true);
        $parsedHtml = $parsedown->text($result['result']);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'success',
                'result' => $parsedHtml,
                'raw_result' => $result['result'],
                'flash_html' => view('App\Views\partials\flash_messages'),
                'token' => csrf_hash()
            ]);
        }

        return redirect()->back()->withInput()
            ->with('result', $parsedHtml)
            ->with('raw_result', $result['result'])
            ->with('success', 'Generated successfully.');
    }
}
