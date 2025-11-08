<?php declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Libraries\GeminiService;
use App\Libraries\MemoryService;
use App\Models\EntityModel;
use App\Models\InteractionModel;
use App\Models\PromptModel;
use App\Models\UserModel;
use App\Models\UserSettingsModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\DocumentService;
use Parsedown;

class GeminiController extends BaseController
{
    protected UserModel $userModel;
    protected GeminiService $geminiService;
    protected PromptModel $promptModel;
    protected UserSettingsModel $userSettingsModel;

    private const SUPPORTED_MIME_TYPES = [
        'image/png', 'image/jpeg', 'image/webp', 'audio/mpeg', 'audio/mp3',
        'audio/wav', 'video/mov', 'video/mpeg', 'video/mp4', 'video/mpg',
        'video/avi', 'video/wmv', 'video/mpegps', 'video/flv',
        'application/pdf', 'text/plain'
    ];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;
    private const USD_TO_KSH_RATE = 129;
    private const DEFAULT_DEDUCTION = 10.00;
    private const MINIMUM_BALANCE = 0.01;

    public function publicPage(): string
    {
        $data = [
            'pageTitle'       => 'AI Studio: Powered by Google Gemini | Afrikenkid',
            'metaDescription' => 'Generate text, analyze PDFs, and chat with a context-aware AI. Our AI Studio leverages Google Gemini for powerful, creative, and analytical tasks.',
            'canonicalUrl'    => url_to('gemini.public'),
            'heroTitle'       => 'Go Beyond Basic Chat',
            'heroSubtitle'    => 'Leverage the power of Google Gemini. Our AI Studio helps you write code, analyze documents, and generate creative content with conversational memory.'
        ];
        return view('gemini/public_page', $data);
    }

    public function __construct()
    {
        $this->userModel         = new UserModel();
        $this->geminiService     = service('geminiService');
        $this->promptModel       = new PromptModel();
        $this->userSettingsModel = new UserSettingsModel();
    }

    public function index(): string
    {
        $userId  = (int) session()->get('userId');
        $prompts = $this->promptModel->where('user_id', $userId)->findAll();

        // **FIX STARTS HERE: Fetch settings and provide defaults**
        $userSetting = $this->userSettingsModel->where('user_id', $userId)->first();
        
        // If a user has no settings row, default assistant mode to ON and voice to OFF.
        // If they do have a row, use the values from the database.
        // The boolean cast in the Entity ensures '0'/'1' become false/true.
        $assistantModeEnabled = $userSetting ? $userSetting->assistant_mode_enabled : true;
        $voiceOutputEnabled   = $userSetting ? $userSetting->voice_output_enabled : false;
        // **FIX ENDS HERE**

        $data = [
            'pageTitle'              => 'AI Studio | Afrikenkid',
            'metaDescription'        => 'Generate content, analyze PDFs, and chat with your AI assistant. Access your saved prompts and manage conversational memory.',
            'canonicalUrl'           => url_to('gemini.index'),
            'result'                 => session()->getFlashdata('result'),
            'error'                  => session()->getFlashdata('error'),
            'prompts'                => $prompts,
            'assistant_mode_enabled' => $assistantModeEnabled,
            'voice_output_enabled'   => $voiceOutputEnabled,
            'audio_url'              => session()->getFlashdata('audio_url'),
        ];
        $data['robotsTag'] = 'noindex, follow';
        return view('gemini/query_form', $data);
    }

    public function uploadMedia(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Authentication required.']);
        }

        $validationRules = [
            'file' => [
                'label' => 'File',
                'rules' => 'uploaded[file]'
                    . '|max_size[file,' . (self::MAX_FILE_SIZE / 1024) . ']'
                    . '|mime_in[file,' . implode(',', self::SUPPORTED_MIME_TYPES) . ']',
            ],
        ];

        if (!$this->validate($validationRules)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['status' => 'error', 'message' => $this->validator->getErrors()['file']]);
        }

        $file = $this->request->getFile('file');

        if (!$file->isValid() || $file->hasMoved()) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['status' => 'error', 'message' => 'Invalid file upload.']);
        }

        $userTempPath = WRITEPATH . 'uploads/gemini_temp/' . $userId . '/';
        if (!is_dir($userTempPath)) {
            mkdir($userTempPath, 0777, true);
        }

        $fileName = $file->getRandomName();
        $file->move($userTempPath, $fileName);

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'status' => 'success',
                'file_id' => $fileName,
                'original_name' => $file->getClientName(),
                'csrf_token' => csrf_hash(),
            ]);
    }

    public function deleteMedia(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Authentication required.']);
        }

        $fileId = $this->request->getPost('file_id');
        if (empty($fileId)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'File ID is missing.']);
        }
        
        $sanitizedId = basename($fileId);
        $filePath = WRITEPATH . 'uploads/gemini_temp/' . $userId . '/' . $sanitizedId;

        if (file_exists($filePath) && is_file($filePath)) {
            if (unlink($filePath)) {
                return $this->response->setStatusCode(200)->setJSON([
                    'status' => 'success', 
                    'message' => 'File deleted.',
                    'csrf_token' => csrf_hash(),
                ]);
            }
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Could not delete the file.']);
        }

        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'File not found.']);
    }

    public function generate(): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        /** @var User|null $user */
        $user = $this->userModel->find($userId);

        if (! $user) {
            return redirect()->back()->withInput()->with('error', 'User not logged in or invalid user ID.');
        }

        $userSetting = $this->userSettingsModel->where('user_id', $userId)->first();
        $isAssistantMode = $userSetting ? $userSetting->assistant_mode_enabled : true;
        $isVoiceOutputMode = $userSetting ? $userSetting->voice_output_enabled : false;

        $inputText = (string) $this->request->getPost('prompt');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');

        $contextData = $this->_prepareContext($userId, $inputText, $isAssistantMode);
        $finalPrompt = $contextData['finalPrompt'];
        
        $uploadResult = $this->_handlePreUploadedFiles($uploadedFileIds, $userId);
        if (isset($uploadResult['error'])) {
            $this->_cleanupTempFiles($uploadedFileIds, $userId);
            return redirect()->back()->withInput()->with('error', $uploadResult['error']);
        }
        $parts = $uploadResult['parts'];

        if ($finalPrompt) {
            array_unshift($parts, ['text' => $finalPrompt]);
        }

        if (empty($parts)) {
            $this->_cleanupTempFiles($uploadedFileIds, $userId);
            return redirect()->back()->withInput()->with('error', 'Prompt or supported media is required.');
        }

        $balanceCheck = $this->_checkBalanceAgainstCost($user, $parts);
        if (isset($balanceCheck['error'])) {
            $this->_cleanupTempFiles($uploadedFileIds, $userId);
            return redirect()->back()->withInput()->with('error', $balanceCheck['error']);
        }

        $apiResponse = $this->geminiService->generateContent($parts);
        $this->_logApiPayload($apiResponse);
        
        $this->_cleanupTempFiles($uploadedFileIds, $userId);

        if (isset($apiResponse['error'])) {
            return redirect()->back()->withInput()->with('error', $apiResponse['error']);
        }

        $audioUrl = null;
        $rawTextResult = $apiResponse['result'];
        if ($isVoiceOutputMode && !empty(trim($rawTextResult))) {
            $speechResponse = $this->geminiService->generateSpeech($rawTextResult);
            if ($speechResponse['status']) {
                $audioUrl = $this->_processAudioData($speechResponse['audioData']);
                if ($audioUrl === null) {
                    session()->setFlashdata('warning', 'Generated voice but failed to process the audio file.');
                }
            } else {
                session()->setFlashdata('warning', 'Could not generate voice output. ' . $speechResponse['error']);
            }
        }

        $this->_processApiResponse($user, $apiResponse, $isAssistantMode, $contextData);

        $parsedown  = new Parsedown();
        $htmlResult = $parsedown->text($rawTextResult);

        $redirect = redirect()->back()->withInput()
            ->with('result', $htmlResult)
            ->with('raw_result', $rawTextResult);
            
        if ($audioUrl) {
            $redirect->with('audio_url', $audioUrl);
        }

        return $redirect;
    }

    public function updateAssistantMode(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Authentication required.',
                'csrf_token' => csrf_hash()
            ]);
        }

        $isEnabled = $this->request->getPost('enabled') === 'true';

        $setting = $this->userSettingsModel->where('user_id', $userId)->first();

        if ($setting) {
            $this->userSettingsModel->update($setting->id, ['assistant_mode_enabled' => $isEnabled]);
        } else {
            $this->userSettingsModel->save([
                'user_id' => $userId,
                'assistant_mode_enabled' => $isEnabled
            ]);
        }

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 'success',
            'message' => 'Setting saved.',
            'csrf_token' => csrf_hash()
        ]);
    }

    public function updateVoiceOutputMode(): ResponseInterface
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Authentication required.',
                'csrf_token' => csrf_hash()
            ]);
        }

        $isEnabled = $this->request->getPost('enabled') === 'true';

        $setting = $this->userSettingsModel->where('user_id', $userId)->first();

        if ($setting) {
            $this->userSettingsModel->update($setting->id, ['voice_output_enabled' => $isEnabled]);
        } else {
            $this->userSettingsModel->save([
                'user_id' => $userId,
                'voice_output_enabled' => $isEnabled
            ]);
        }

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 'success',
            'message' => 'Setting saved.',
            'csrf_token' => csrf_hash()
        ]);
    }
    
    private function _prepareContext(int $userId, string $inputText, bool $isAssistantMode): array
    {
        $contextData = [
            'finalPrompt'        => $inputText,
            'memoryService'      => null,
            'usedInteractionIds' => [],
        ];

        if ($isAssistantMode && ! empty(trim($inputText))) {
            /** @var MemoryService $memoryService */
            $memoryService = service('memory', $userId);
            $recalled      = $memoryService->getRelevantContext($inputText);
            $context       = $recalled['context'];

            $systemPrompt = $memoryService->getTimeAwareSystemPrompt();
            $currentTime  = "CURRENT_TIME: " . date('Y-m-d H:i:s T');
            $finalPrompt  = "{$systemPrompt}\n\n---RECALLED CONTEXT---\n{$context}---END CONTEXT---\n\n{$currentTime}\n\nUser query: \"{$inputText}\"";

            $contextData['finalPrompt']        = $finalPrompt;
            $contextData['memoryService']      = $memoryService;
            $contextData['usedInteractionIds'] = $recalled['used_interaction_ids'];
        }

        return $contextData;
    }
    
    private function _handlePreUploadedFiles(array $fileIds, int $userId): array
    {
        $parts = [];
        $userTempPath = WRITEPATH . 'uploads/gemini_temp/' . $userId . '/';

        foreach ($fileIds as $fileId) {
            $sanitizedId = basename($fileId);
            $filePath = $userTempPath . $sanitizedId;

            if (!file_exists($filePath) || !is_file($filePath)) {
                log_message('error', "User {$userId}'s temporary file not found: {$filePath}");
                return ['error' => "An uploaded file could not be processed. Please try uploading again."];
            }

            $mimeType = mime_content_type($filePath);
            if ($mimeType === false || !in_array($mimeType, self::SUPPORTED_MIME_TYPES, true)) {
                return ['error' => "Unsupported file type detected for: " . esc($sanitizedId)];
            }

            $fileContents = file_get_contents($filePath);
            if ($fileContents === false) {
                 return ['error' => "Could not read file: " . esc($sanitizedId)];
            }
            
            $base64Content = base64_encode($fileContents);
            $parts[] = ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64Content]];
        }

        return ['parts' => $parts];
    }
    
    private function _cleanupTempFiles(array $fileIds, int $userId): void
    {
        $userTempPath = WRITEPATH . 'uploads/gemini_temp/' . $userId . '/';
        foreach ($fileIds as $fileId) {
            $sanitizedId = basename($fileId);
            $filePath = $userTempPath . $sanitizedId;
            if (file_exists($filePath) && is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    private function _checkBalanceAgainstCost(User $user, array $parts): array
    {
        $tokenCountResponse = $this->geminiService->countTokens($parts);

        if (! $tokenCountResponse['status']) {
            return ['error' => $tokenCountResponse['error']];
        }

        $inputTokens = $tokenCountResponse['totalTokens'];
        $costData    = $this->_calculateCost($inputTokens, 0);

        $requiredBalance = max(self::MINIMUM_BALANCE, $costData['costInKSH']);

        if (bccomp((string) $user->balance, (string) $requiredBalance, 2) < 0) {
            $errorMessage = "Insufficient balance. This query costs approx. KSH " . number_format($requiredBalance, 2) .
                            ", but you only have KSH " . $user->balance . ".";
            return ['error' => $errorMessage];
        }

        return [];
    }

    private function _processApiResponse(User $user, array $apiResponse, bool $isAssistantMode, array $contextData): void
    {
        $inputTokens  = (int) ($apiResponse['usage']['promptTokenCount'] ?? 0);
        $outputTokens = (int) ($apiResponse['usage']['candidatesTokenCount'] ?? 0);

        $costData = $this->_calculateCost($inputTokens, $outputTokens);

        $db = \Config\Database::connect();
        $db->transStart();

        $deductionSuccess = $this->userModel->deductBalance((int) $user->id, (string) $costData['deductionAmount']);

        if ($isAssistantMode && isset($contextData['memoryService'])) {
            /** @var MemoryService $memoryService */
            $memoryService = $contextData['memoryService'];
            $aiResponseText = $apiResponse['result'];
            $memoryService->updateMemory(
                (string) $this->request->getPost('prompt'),
                $aiResponseText,
                $contextData['usedInteractionIds']
            );
        }

        $db->transComplete();

        if ($db->transStatus() === false || !$deductionSuccess) {
            log_message('critical', "Transaction failed during AI response processing for user ID: {$user->id}");
            session()->setFlashdata('error', 'Query processed, but a billing or memory error occurred. Please contact support.');
        } else {
            session()->setFlashdata('success', $costData['costMessage']);
        }
    }

    private function _calculateCost(int $inputTokens, int $outputTokens): array
    {
        if ($inputTokens === 0 && $outputTokens === 0) {
            return [
                'deductionAmount' => self::DEFAULT_DEDUCTION,
                'costMessage'     => "A default charge of KSH " . number_format(self::DEFAULT_DEDUCTION, 2) . " has been applied.",
                'costInKSH'       => self::DEFAULT_DEDUCTION,
            ];
        }

        // Pricing for Gemini 2.5 Pro (160%)
        $isTierOne = $inputTokens <= 128000;
        $inputPricePerMillion  = $isTierOne ? 3.25 : 4.00;
        $outputPricePerMillion = $isTierOne ? 16.00 : 24.00;

        $inputCostUSD  = ($inputTokens / 1000000) * $inputPricePerMillion;
        $outputCostUSD = ($outputTokens / 1000000) * $outputPricePerMillion;
        $totalCostUSD  = $inputCostUSD + $outputCostUSD;
        $costInKSH     = $totalCostUSD * self::USD_TO_KSH_RATE;

        $deductionAmount = max(self::MINIMUM_BALANCE, ceil($costInKSH * 100) / 100);

        return [
            'deductionAmount' => $deductionAmount,
            'costMessage'     => "KSH " . number_format($deductionAmount, 2) . " deducted for your AI query.",
            'costInKSH'       => $costInKSH,
        ];
    }

    private function _logApiPayload(array $apiResponse): void
    {
        $logFilePath = WRITEPATH . 'logs/gemini_payload.log';
        $logContent  = json_encode($apiResponse, JSON_PRETTY_PRINT);
        file_put_contents($logFilePath, $logContent . PHP_EOL, FILE_APPEND);
    }

    public function addPrompt(): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return redirect()->to(url_to('gemini.index'))->with('error', 'You must be logged in.');
        }

        $validation = $this->validate([
            'title'       => 'required|max_length[255]',
            'prompt_text' => 'required',
        ]);

        if (! $validation) {
            return redirect()->to(url_to('gemini.index'))->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'user_id'     => $userId,
            'title'       => $this->request->getPost('title'),
            'prompt_text' => $this->request->getPost('prompt_text'),
        ];

        if ($this->promptModel->save($data)) {
            return redirect()->to(url_to('gemini.index'))->with('success', 'Prompt saved successfully.');
        }

        return redirect()->to(url_to('gemini.index'))->with('error', 'Failed to save the prompt.');
    }

    public function deletePrompt(int $id): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return redirect()->to(url_to('gemini.index'))->with('error', 'You must be logged in.');
        }

        $prompt = $this->promptModel->find($id);

        if (! $prompt || (int) $prompt->user_id !== $userId) {
            return redirect()->to(url_to('gemini.index'))->with('error', 'You are not authorized to delete this prompt.');
        }

        if ($this->promptModel->delete($id)) {
            return redirect()->to(url_to('gemini.index'))->with('success', 'Prompt deleted successfully.');
        }

        return redirect()->to(url_to('gemini.index'))->with('error', 'Failed to delete the prompt.');
    }

    public function clearMemory(): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return redirect()->to(url_to('gemini.index'))->with('error', 'You must be logged in to perform this action.');
        }

        $interactionModel = new InteractionModel();
        $entityModel      = new EntityModel();

        $db = \Config\Database::connect();
        $db->transStart();

        $interactionModel->where('user_id', $userId)->delete();
        $entityModel->where('user_id', $userId)->delete();

        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('error', 'Failed to clear memory for user ID: ' . $userId);
            return redirect()->to(url_to('gemini.index'))->with('error', 'An error occurred while trying to clear your memory. Please try again.');
        }

        return redirect()->to(url_to('gemini.index'))->with('success', 'Your conversational memory has been successfully cleared.');
    }

    /**
     * Handles the download request for generated content in various formats (PDF, Word).
     * It uses the DocumentService, which attempts to use Pandoc and falls back to Dompdf for PDFs.
     *
     * @return ResponseInterface|RedirectResponse|void
     */
    public function downloadDocument()
    {
        $markdownContent = $this->request->getPost('raw_response');
        $format = $this->request->getPost('format');

        if (empty($markdownContent) || !in_array($format, ['pdf', 'docx'])) {
            return redirect()->back()->with('error', 'Invalid content or format for document generation.');
        }

        /** @var DocumentService $documentService */
        $documentService = service('documentService');
        $result = $documentService->generate($markdownContent, $format);

        if (str_starts_with($result['status'], 'success')) {
            // Pandoc success (file path) or Dompdf success (raw data)
            $filename = 'AI-Studio-Output-' . uniqid() . '.' . ($format === 'docx' ? 'docx' : 'pdf');

            // Clean output buffer before sending the file
            if (ob_get_level()) {
                ob_end_clean();
            }

            if ($result['status'] === 'success' && isset($result['filePath'])) {
                // Pandoc generated a file
                $filePath = $result['filePath'];
                header('Content-Type: ' . ($format === 'docx' ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : 'application/pdf'));
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
                unlink($filePath); // Clean up the temp file
                exit();
            } elseif ($result['status'] === 'success_fallback' && isset($result['fileData'])) {
                // Dompdf generated raw data
                return $this->response
                    ->setStatusCode(200)
                    ->setContentType('application/pdf')
                    ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->setBody($result['fileData']);
            }
        }

        // If we reach here, both Pandoc and the fallback failed.
        return redirect()->back()->with('error', $result['message'] ?? 'An unknown error occurred during document generation.');
    }
    
/**
     * Processes raw audio data: saves, converts, and returns the secure filename.
     *
     * @param string $base64AudioData Base64 encoded raw PCM audio data.
     * @return string|null The filename of the converted MP3 file, or null on failure.
     */
    private function _processAudioData(string $base64AudioData): ?string
    {
        // 1. Define paths and ensure directories exist
        $userId = (int) session()->get('userId');
        $tempPath = WRITEPATH . 'uploads/ttsaudio_temp/' . $userId . '/';

        // --- FIX: Save the final file to a secure, non-public directory inside WRITEPATH ---
        $securePath = WRITEPATH . 'uploads/ttsaudio_secure/' . $userId . '/';

        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0775, true);
        }
        if (!is_dir($securePath)) {
            mkdir($securePath, 0775, true);
        }

        // 2. Save temporary raw file
        $rawFileName = uniqid('audio_', true) . '.raw';
        $rawFilePath = $tempPath . $rawFileName;
        $decodedData = base64_decode($base64AudioData);
        if (file_put_contents($rawFilePath, $decodedData) === false) {
            log_message('error', 'Failed to save temporary raw audio file.');
            return null;
        }

        // 3. Convert to MP3
        $mp3FileName = str_replace('.raw', '.mp3', $rawFileName);
        $mp3FilePath = $securePath . $mp3FileName;
        $ffmpegService = service('ffmpegService');
        $conversionSuccess = $ffmpegService->convertPcmToMp3($rawFilePath, $mp3FilePath);

        // 4. Cleanup temporary file
        if (file_exists($rawFilePath)) {
            unlink($rawFilePath);
        }

        // 5. Return just the filename on success, not the full URL.
        if ($conversionSuccess) {
            return $mp3FileName;
        }

        log_message('error', 'Failed to convert audio file to MP3.');
        return null;
    }


    public function serveAudio(string $fileName)
    {
        // Security Check 1: Ensure user is logged in
        if (! session()->get('isLoggedIn')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Security Check 2: Sanitize filename to prevent directory traversal attacks
        $sanitizedName = basename($fileName);
        $userId = (int) session()->get('userId');
        
        // Construct the full, secure path (this MUST match the path in _processAudioData)
        $filePath = WRITEPATH . 'uploads/ttsaudio_secure/' . $userId . '/' . $sanitizedName;

        if (file_exists($filePath)) {
            // Use CodeIgniter's built-in download response, which handles headers.
            // The first parameter is the path, and the second is the raw data (null here).
            // This will stream the file without revealing its location.
            return $this->response->download($filePath, null)->setFileName($sanitizedName);
        }

        // If file not found, show a 404 error
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }
}