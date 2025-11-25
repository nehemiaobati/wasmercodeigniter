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
use Parsedown;

class GeminiController extends BaseController
{
    protected UserModel $userModel;
    protected GeminiService $geminiService;
    protected PromptModel $promptModel;
    protected UserSettingsModel $userSettingsModel;

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
    private const MAX_FILE_SIZE = 150 * 1024 * 1024; // 10MB
    private const USD_TO_KSH_RATE = 129;
    private const DEFAULT_DEDUCTION = 10.00;
    private const MINIMUM_BALANCE = 0.01;

    public function __construct()
    {
        $this->userModel         = new UserModel();
        $this->geminiService     = service('geminiService');
        $this->promptModel       = new PromptModel();
        $this->userSettingsModel = new UserSettingsModel();
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
            'metaDescription' => 'Transform how you work with AI. Generate professional content, extract insights from PDFs, and convert text to speech using our advanced, context-aware platform.',
            'canonicalUrl'    => url_to('gemini.public'),
            'heroTitle'       => 'Enterprise-Grade AI Solutions',
            'heroSubtitle'    => 'A complete suite for content generation, intelligent document processing, and audio synthesis - tailored for your workflow.'
        ];
        return view('App\Modules\Gemini\Views\gemini\public_page.php', $data);
    }

    /**
     * Displays the main application dashboard.
     *
     * @return string The rendered view.
     */
    public function index(): string
    {
        $userId = (int) session()->get('userId');
        $prompts = $this->promptModel->where('user_id', $userId)->findAll();
        $userSetting = $this->userSettingsModel->where('user_id', $userId)->first();

        $data = [
            'pageTitle'              => 'AI Workspace | Afrikenkid',
            'metaDescription'        => 'Your personal AI workspace for content creation and data analysis.',
            'canonicalUrl'           => url_to('gemini.index'),
            'result'                 => session()->getFlashdata('result'),
            'error'                  => session()->getFlashdata('error'),
            'prompts'                => $prompts,
            'assistant_mode_enabled' => $userSetting ? $userSetting->assistant_mode_enabled : true,
            'voice_output_enabled'   => $userSetting ? $userSetting->voice_output_enabled : false,
            'audio_url'              => session()->getFlashdata('audio_url'),
            'maxFileSize'            => self::MAX_FILE_SIZE,
            'supportedMimeTypes'     => json_encode(self::SUPPORTED_MIME_TYPES),
        ];
        $data['robotsTag'] = 'noindex, follow';

        return view('App\Modules\Gemini\Views\gemini\query_form', $data);
    }

    /**
     * Handles file uploads for the Gemini context.
     *
     * @return ResponseInterface JSON response with upload status.
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
            mkdir($userTempPath, 0777, true);
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
     *
     * @return RedirectResponse Redirects back with results or errors.
     */
    public function generate(): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        $user = $this->userModel->find($userId);

        if (!$user) return redirect()->back()->with('error', 'User not found.');

        // Input Validation
        if (!$this->validate([
            'prompt' => 'max_length[200000]'
        ])) {
            return redirect()->back()->withInput()->with('error', 'Prompt is too long. Maximum 200,000 characters allowed.');
        }

        $userSetting = $this->userSettingsModel->where('user_id', $userId)->first();
        $isAssistantMode = $userSetting ? $userSetting->assistant_mode_enabled : true;
        $isVoiceMode = $userSetting ? $userSetting->voice_output_enabled : false;

        $inputText = (string) $this->request->getPost('prompt');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');

        // 1. Prepare Context & Files
        $contextData = $this->_prepareContext($userId, $inputText, $isAssistantMode);
        $uploadResult = $this->_handlePreUploadedFiles($uploadedFileIds, $userId);

        if (isset($uploadResult['error'])) {
            $this->_cleanupTempFiles($uploadedFileIds, $userId);
            return redirect()->back()->withInput()->with('error', $uploadResult['error']);
        }

        $parts = $uploadResult['parts'];
        if ($contextData['finalPrompt']) {
            array_unshift($parts, ['text' => $contextData['finalPrompt']]);
        }

        if (empty($parts)) {
            $this->_cleanupTempFiles($uploadedFileIds, $userId);
            return redirect()->back()->withInput()->with('error', 'Please provide a prompt or file.');
        }

        // 2. Check Balance
        $balanceCheck = $this->_checkBalanceAgainstCost($user, $parts);
        if (isset($balanceCheck['error'])) {
            $this->_cleanupTempFiles($uploadedFileIds, $userId);
            return redirect()->back()->withInput()->with('error', $balanceCheck['error']);
        }

        // 3. Call API
        $apiResponse = $this->geminiService->generateContent($parts);
        $this->_cleanupTempFiles($uploadedFileIds, $userId);

        if (isset($apiResponse['error'])) {
            return redirect()->back()->withInput()->with('error', $apiResponse['error']);
        }

        // 4. Handle Audio (Voice Mode)
        $audioUrl = null;
        $audioFilePath = null;
        $audioUsage = null;

        if ($isVoiceMode && !empty(trim($apiResponse['result']))) {
            $speech = $this->geminiService->generateSpeech($apiResponse['result']);
            if ($speech['status']) {
                $audioUrl = $this->_processAudioData($speech['audioData']);
                $audioUsage = $speech['usage'] ?? null;
                // Store the absolute file path for the view to read
                $userId = (int) session()->get('userId');
                $audioFilePath = WRITEPATH . 'uploads/ttsaudio_secure/' . $userId . '/' . basename($audioUrl);
            }
        }

        // 5. Process Payment & Memory
        $this->_processApiResponse($user, $apiResponse, $isAssistantMode, $contextData, $audioUsage);

        // 6. Output
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        $parsedown->setBreaksEnabled(true);

        $redirect = redirect()->back()->withInput()
            ->with('result', $parsedown->text($apiResponse['result']))
            ->with('raw_result', $apiResponse['result']);

        if ($audioUrl) {
            $redirect->with('audio_url', $audioUrl);
        }
        if ($audioFilePath && file_exists($audioFilePath)) {
            $redirect->with('audio_file_path', $audioFilePath);
        }

        return $redirect;
    }

    /**
     * Unified Settings Update Method
     */
    /**
     * Updates user settings (Assistant Mode, Voice Output).
     *
     * @return ResponseInterface JSON response with update status.
     */
    public function updateSetting(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) return $this->response->setStatusCode(403);

        $settingKey = $this->request->getPost('setting_key'); // 'assistant_mode_enabled' or 'voice_output_enabled'
        $isEnabled = $this->request->getPost('enabled') === 'true';

        if (!in_array($settingKey, ['assistant_mode_enabled', 'voice_output_enabled'])) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid setting']);
        }

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
     * @return RedirectResponse Redirects back with success or error message.
     */
    public function addPrompt(): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        if (!$this->validate(['title' => 'required|max_length[255]', 'prompt_text' => 'required'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid input.');
        }

        $this->promptModel->save([
            'user_id' => $userId,
            'title' => $this->request->getPost('title'),
            'prompt_text' => $this->request->getPost('prompt_text')
        ]);
        return redirect()->back()->with('success', 'Prompt saved.');
    }

    /**
     * Deletes a saved prompt.
     *
     * @param int $id The ID of the prompt to delete.
     * @return RedirectResponse Redirects back with success or error message.
     */
    public function deletePrompt(int $id): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        $prompt = $this->promptModel->find($id);
        if ($prompt && $prompt->user_id == $userId) {
            $this->promptModel->delete($id);
            return redirect()->back()->with('success', 'Prompt deleted.');
        }
        return redirect()->back()->with('error', 'Unauthorized.');
    }

    /**
     * Clears the user's interaction memory and entities.
     *
     * @return RedirectResponse Redirects back with success or error message.
     */
    public function clearMemory(): RedirectResponse
    {
        $userId = (int) session()->get('userId');

        // Ensure transaction handling is robust
        $db = \Config\Database::connect();
        $db->transStart();

        (new InteractionModel())->where('user_id', $userId)->delete();
        (new EntityModel())->where('user_id', $userId)->delete();

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Failed to clear memory.');
        }

        return redirect()->back()->with('success', 'Memory cleared.');
    }

    /**
     * Serves the file with correct headers for inline playback.
     *
     * @param string $fileName The name of the file to serve.
     * @return ResponseInterface The file response.
     * @throws \CodeIgniter\Exceptions\PageNotFoundException If the file does not exist.
     */
    public function serveAudio(string $fileName)
    {
        $userId = (int) session()->get('userId');

        // Security: Basename prevents directory traversal attacks
        $path = WRITEPATH . 'uploads/ttsaudio_secure/' . $userId . '/' . basename($fileName);

        if (!file_exists($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Detect MIME type dynamically based on the actual file extension
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = ($ext === 'wav') ? 'audio/wav' : 'audio/mpeg';

        // 'inline' disposition allows the <audio> tag to play it immediately
        $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string)filesize($path))
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"');

        // Efficiently output file content without loading into memory
        readfile($path);
        return $this->response;
    }

    /**
     * Generates and downloads a document (PDF or DOCX) from the content.
     *
     * @return ResponseInterface|RedirectResponse The file download or redirect on error.
     */
    public function downloadDocument()
    {
        $content = $this->request->getPost('raw_response');
        $format = $this->request->getPost('format');

        // Basic Validation
        if (!$content || !in_array($format, ['pdf', 'docx'])) {
            return redirect()->back()->with('error', 'Invalid request parameters.');
        }

        // Execution: Service now guarantees a 'fileData' string on success
        $result = service('documentService')->generate($content, $format);

        if ($result['status'] === 'success') {
            $mime = $format === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                : 'application/pdf';

            $filename = 'Studio-Output-' . date('Ymd-His') . '.' . $format;

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

    private function _prepareContext(int $userId, string $inputText, bool $isAssistantMode): array
    {
        $data = ['finalPrompt' => $inputText, 'memoryService' => null, 'usedInteractionIds' => []];

        if ($isAssistantMode && !empty(trim($inputText))) {
            $memoryService = service('memory', $userId);
            $recalled = $memoryService->getRelevantContext($inputText);

            $template = $memoryService->getTimeAwareSystemPrompt();
            $template = str_replace('{{CURRENT_TIME}}', date('Y-m-d H:i:s T'), $template);
            $template = str_replace('{{CONTEXT_FROM_MEMORY_SERVICE}}', $recalled['context'], $template);
            $template = str_replace('{{USER_QUERY}}', htmlspecialchars($inputText), $template);
            $template = str_replace('{{TONE_INSTRUCTION}}', "Maintain default persona: dry, witty, concise.", $template);

            $data['finalPrompt'] = $template;
            $data['memoryService'] = $memoryService;
            $data['usedInteractionIds'] = $recalled['used_interaction_ids'];
        }
        return $data;
    }

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

    private function _cleanupTempFiles(array $fileIds, int $userId): void
    {
        foreach ($fileIds as $fileId) {
            @unlink(WRITEPATH . 'uploads/gemini_temp/' . $userId . '/' . basename($fileId));
        }
    }

    private function _checkBalanceAgainstCost(User $user, array $parts): array
    {
        $response = $this->geminiService->countTokens($parts);
        if (!$response['status']) return ['error' => $response['error']];

        $costData = $this->_calculateCost(['promptTokenCount' => $response['totalTokens']], null);

        if ($user->balance < $costData['costInKSH']) {
            return ['error' => "Insufficient balance. Cost: KSH " . number_format($costData['costInKSH'], 2)];
        }
        return [];
    }

    private function _processApiResponse(User $user, array $apiResponse, bool $isAssistantMode, array $contextData, ?array $audioUsage = null): void
    {
        // Calculate Total Cost
        $costData = $this->_calculateCost($apiResponse['usage'] ?? [], $audioUsage);

        $db = \Config\Database::connect();
        $db->transStart();

        $this->userModel->deductBalance((int)$user->id, (string)$costData['deductionAmount']);

        if ($isAssistantMode && isset($contextData['memoryService'])) {
            $contextData['memoryService']->updateMemory(
                (string)$this->request->getPost('prompt'),
                $apiResponse['result'],
                $contextData['usedInteractionIds']
            );
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('critical', "Transaction failed User {$user->id}");
            session()->setFlashdata('error', 'System error processing transaction.');
        } else {
            session()->setFlashdata('success', $costData['costMessage']);
        }
    }

    private function _calculateCost(array $textUsage, ?array $audioUsage = null): array
    {
        // Audio Pricing (Per 1M tokens)
        $audioInputPrice = 0.50;
        $audioOutputPrice = 10.00;

        // 1. Text Pricing (Gemini 3 Pro Preview)
        $textInput = (int)($textUsage['promptTokenCount'] ?? 0);
        $textOutput = (int)($textUsage['candidatesTokenCount'] ?? 0);

        $tier1 = $textInput <= 200000;
        $inRate = ($tier1 ? 2.00 : 4.00) * 1.60; // USD per million, +60%
        $outRate = ($tier1 ? 12.00 : 18.00) * 1.60; // USD per million, +60%

        $textUsd = (($textInput / 1e6) * $inRate) + (($textOutput / 1e6) * $outRate);

        // 2. Audio Pricing
        $audioInput = (int)($audioUsage['promptTokenCount'] ?? 0);
        $audioOutput = (int)($audioUsage['candidatesTokenCount'] ?? 0);

        $audioUsd = (($audioInput / 1e6) * $audioInputPrice) + (($audioOutput / 1e6) * $audioOutputPrice);

        // 3. Total
        $totalUsd = $textUsd + $audioUsd;
        $totalKsh = $totalUsd * self::USD_TO_KSH_RATE;

        // 4. Minimum Deduction Rule
        // If there was ANY usage (text or audio), enforce minimum charge.
        $hasUsage = ($textInput + $textOutput + $audioInput + $audioOutput) > 0;
        $deduction = ($hasUsage && $totalKsh < self::MINIMUM_BALANCE)
            ? self::MINIMUM_BALANCE
            : ceil($totalKsh * 100) / 100;

        // Fallback for zero usage (shouldn't happen in success path but safe to keep)
        if (!$hasUsage) {
            $deduction = self::DEFAULT_DEDUCTION;
        }

        return [
            'deductionAmount' => $deduction,
            'costMessage' => "KSH " . number_format($deduction, 2) . " deducted.",
            'costInKSH' => $totalKsh
        ];
    }

    /**
     * Handles the storage and conversion of the raw audio.
     */
    private function _processAudioData(string $base64Data): ?string
    {
        $userId = (int) session()->get('userId');
        $securePath = WRITEPATH . 'uploads/ttsaudio_secure/' . $userId . '/';

        if (!is_dir($securePath)) mkdir($securePath, 0775, true);

        // Generate base name (e.g., "speech_651a...")
        $filenameBase = uniqid('speech_');

        // Delegate to Service (Returns .mp3 OR .wav)
        $result = service('ffmpegService')->processAudio(
            $base64Data,
            $securePath,
            $filenameBase
        );

        return $result['success'] ? $result['fileName'] : null;
    }
}
