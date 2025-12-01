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

class OllamaController extends BaseController
{
    protected UserModel $userModel;
    protected OllamaService $ollamaService;
    protected OllamaPromptModel $promptModel;
    protected OllamaUserSettingsModel $userSettingsModel;

    private const SUPPORTED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/jpg', // Some clients send this
        'image/webp',
        'image/gif',
        //'application/pdf',
    ];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_FILES = 3;
    private const COST_PER_REQUEST = 1.00; // Flat rate per request

    public function __construct()
    {
        $this->userModel         = new UserModel();
        $this->ollamaService     = new OllamaService();
        $this->promptModel       = new OllamaPromptModel();
        $this->userSettingsModel = new OllamaUserSettingsModel();
    }

    /**
     * Displays the main application dashboard.
     */
    public function index(): string
    {
        $userId = (int) session()->get('userId');
        $prompts = $this->promptModel->where('user_id', $userId)->findAll();
        $userSetting = $this->userSettingsModel->where('user_id', $userId)->first();

        // Fetch available models
        $availableModels = $this->ollamaService->getModels();
        if (empty($availableModels)) {
            $availableModels = ['llama3']; // Fallback
        }

        $data = [
            'pageTitle'              => 'Local AI Workspace | Ollama',
            'metaDescription'        => 'Interact with local LLMs via Ollama.',
            'canonicalUrl'           => url_to('ollama.index'),
            'result'                 => session()->getFlashdata('result'),
            'error'                  => session()->getFlashdata('error'),
            'prompts'                => $prompts,
            'assistant_mode_enabled' => $userSetting ? $userSetting->assistant_mode_enabled : true,
            'maxFileSize'            => self::MAX_FILE_SIZE,
            'maxFiles'               => self::MAX_FILES,
            'supportedMimeTypes'     => json_encode(self::SUPPORTED_MIME_TYPES),
            'availableModels'        => $availableModels,
        ];
        $data['robotsTag'] = 'noindex, follow';

        return view('App\Modules\Ollama\Views\ollama\query_form', $data);
    }

    /**
     * Handles file uploads.
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
        $userTempPath = WRITEPATH . 'uploads/ollama_temp/' . $userId . '/';

        if (!is_dir($userTempPath)) {
            mkdir($userTempPath, 0755, true);
        }

        $fileName = $file->getRandomName();
        if (!$file->move($userTempPath, $fileName)) {
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
     */
    public function deleteMedia(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) return $this->response->setStatusCode(403);

        $fileId = $this->request->getPost('file_id');
        if (!$fileId) return $this->response->setStatusCode(400);

        $filePath = WRITEPATH . 'uploads/ollama_temp/' . $userId . '/' . basename($fileId);

        if (file_exists($filePath) && unlink($filePath)) {
            return $this->response->setJSON(['status' => 'success', 'csrf_token' => csrf_hash()]);
        }

        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'File not found']);
    }

    /**
     * Generates content using Ollama with Memory Integration.
     */
    public function generate(): RedirectResponse
    {
        // Timeout Safety: Prevent PHP timeouts during slow local LLM inference
        set_time_limit(300);

        $userId = (int) session()->get('userId');
        $user = $this->userModel->find($userId);

        if (!$user) return redirect()->back()->with('error', 'User not found.');

        // Input Validation
        if (!$this->validate([
            'prompt' => 'max_length[100000]',
            'model'  => 'required'
        ])) {
            return redirect()->back()->withInput()->with('error', 'Invalid input.');
        }

        $inputText = (string) $this->request->getPost('prompt');
        $selectedModel = (string) $this->request->getPost('model');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');

        $userSetting = $this->userSettingsModel->where('user_id', $userId)->first();
        $isAssistantMode = $userSetting ? $userSetting->assistant_mode_enabled : true;

        // 1. Check Balance
        if ($user->balance < self::COST_PER_REQUEST) {
            return redirect()->back()->withInput()->with('error', 'Insufficient balance.');
        }

        // 2. Handle Files (Multimodal)
        $images = [];
        $userTempPath = WRITEPATH . 'uploads/ollama_temp/' . $userId . '/';
        foreach ($uploadedFileIds as $fileId) {
            $filePath = $userTempPath . basename($fileId);
            if (file_exists($filePath)) {
                $images[] = base64_encode(file_get_contents($filePath));
                @unlink($filePath); // Cleanup immediately
            }
        }

        $response = [];

        if (!empty($images)) {
            // Multimodal Request (Direct API, no RAG for now)
            $messages = [
                ['role' => 'user', 'content' => $inputText, 'images' => $images]
            ];
            $response = $this->ollamaService->generateChat($selectedModel, $messages);
        } elseif ($isAssistantMode) {
            // Text-only Request with Assistant Mode (Use MemoryService for RAG)
            $memoryService = new \App\Modules\Ollama\Libraries\OllamaMemoryService($userId);
            $response = $memoryService->processChat($inputText, $selectedModel);
        } else {
            // Simple Text Request (Direct API, no Memory)
            $messages = [
                ['role' => 'user', 'content' => $inputText]
            ];
            $response = $this->ollamaService->generateChat($selectedModel, $messages);
        }

        if (isset($response['error']) || (isset($response['success']) && !$response['success'])) {
            $msg = $response['error'] ?? 'Unknown error';
            return redirect()->back()->withInput()->with('error', $msg);
        }

        // Normalize response format
        $resultText = $response['result'] ?? $response['response'] ?? '';

        // 3. Deduct Balance
        $this->userModel->deductBalance((int)$user->id, (string)self::COST_PER_REQUEST);

        // 4. Output
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);

        return redirect()->back()->withInput()
            ->with('result', $parsedown->text($resultText))
            ->with('raw_result', $resultText)
            ->with('success', 'Generated successfully. Cost: ' . self::COST_PER_REQUEST . ' credits.');
    }

    public function updateSetting(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        $key = $this->request->getPost('setting_key');
        $enabled = $this->request->getPost('enabled') === 'true';

        if (!in_array($key, ['assistant_mode_enabled'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid setting']);
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

        // Clear Interactions
        $interactionModel = new OllamaInteractionModel();
        $interactionModel->where('user_id', $userId)->delete();

        // Clear Entities
        $entityModel = new OllamaEntityModel();
        $entityModel->where('user_id', $userId)->delete();

        return redirect()->back()->with('success', 'Memory cleared.');
    }

    /**
     * Downloads the generated content as a document.
     */
    public function downloadDocument()
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) return redirect()->back()->with('error', 'Auth required.');

        $content = $this->request->getPost('content');
        $format  = $this->request->getPost('format');

        if (empty($content) || !in_array($format, ['pdf', 'docx'])) {
            return redirect()->back()->with('error', 'Invalid content or format.');
        }

        $docService = new OllamaDocumentService();
        $result = $docService->generate($content, $format, [
            'author' => 'Ollama User ' . $userId
        ]);

        if ($result['status'] === 'success') {
            $filename = 'ollama_export_' . date('Ymd_His') . '.' . $format;
            $contentType = ($format === 'pdf') ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

            return $this->response
                ->setHeader('Content-Type', $contentType)
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setBody($result['fileData']);
        }

        return redirect()->back()->with('error', $result['message'] ?? 'Export failed.');
    }
}
