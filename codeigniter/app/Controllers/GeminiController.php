<?php declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Libraries\GeminiService;
use App\Libraries\MemoryService;
use App\Models\PromptModel;
use App\Models\UserModel;
use App\Models\UserSettingsModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\Files\UploadedFile;
use Parsedown;

/**
 * Class GeminiController
 *
 * Handles interactions with the Gemini AI model, including processing user prompts,
 * managing file uploads, calculating costs, and handling responses.
 */
class GeminiController extends BaseController
{
    /**
     * @var UserModel
     */
    protected UserModel $userModel;

    /**
     * @var GeminiService
     */
    protected GeminiService $geminiService;

    /**
     * @var PromptModel
     */
    protected PromptModel $promptModel;

    /**
     * @var UserSettingsModel
     */
    protected UserSettingsModel $userSettingsModel;

    /**
     * Supported MIME types for file uploads.
     * @var array<string>
     */
    private const SUPPORTED_MIME_TYPES = [
        'image/png', 'image/jpeg', 'image/webp', 'audio/mpeg', 'audio/mp3',
        'audio/wav', 'video/mov', 'video/mpeg', 'video/mp4', 'video/mpg',
        'video/avi', 'video/wmv', 'video/mpegps', 'video/flv',
        'application/pdf', 'text/plain'
    ];

    /**
     * Maximum size for a single uploaded file (10 MB).
     * @var int
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * USD to KSH conversion rate.
     * @var int
     */
    private const USD_TO_KSH_RATE = 129;

    /**
     * Default fallback cost for a query in KSH.
     * @var float
     */
    private const DEFAULT_DEDUCTION = 10.00;

    /**
     * Minimum required balance to attempt a query.
     * @var float
     */
    private const MINIMUM_BALANCE = 0.01;

    /**
     * GeminiController constructor.
     */
    public function __construct()
    {
        $this->userModel         = new UserModel();
        $this->geminiService     = service('geminiService');
        $this->promptModel       = new PromptModel();
        $this->userSettingsModel = new UserSettingsModel();
    }

    /**
     * Displays the main query form with user's saved prompts and settings.
     *
     * @return string The rendered view.
     */
    public function index(): string
    {
        $userId  = (int) session()->get('userId');
        $prompts = $this->promptModel->where('user_id', $userId)->findAll();

        // Fetch or create user setting for Assistant Mode
        $userSetting = $this->userSettingsModel->where('user_id', $userId)->first();
        if (!$userSetting) {
            $this->userSettingsModel->save([
                'user_id' => $userId,
                'assistant_mode_enabled' => true, // Default to enabled for new users
            ]);
            $assistantModeEnabled = true;
        } else {
            $assistantModeEnabled = $userSetting->assistant_mode_enabled;
        }

        $data = [
            'pageTitle'   => 'Gemini AI Studio | Afrikenkid',
            'metaDescription' => 'Access Google\'s powerful Gemini AI. Craft prompts, attach media, and generate content with assistant-level context and memory.',
            'canonicalUrl' => url_to('gemini.index'),
            'result'  => session()->getFlashdata('result'),
            'error'   => session()->getFlashdata('error'),
            'prompts' => $prompts,
            'assistant_mode_enabled' => $assistantModeEnabled, // Pass setting to view
        ];
        return view('gemini/query_form', $data);
    }

    /**
     * Handles a single, asynchronous file upload and stores it temporarily in a user-specific directory.
     *
     * @return ResponseInterface A JSON response indicating success or failure.
     */
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

    /**
     * Deletes a single temporary file from the current user's temporary directory.
     *
     * @return ResponseInterface
     */
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

    /**
     * Processes the user's prompt, generates content via Gemini API,
     * and handles the response.
     *
     * @return RedirectResponse
     */
    public function generate(): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        /** @var User|null $user */
        $user = $this->userModel->find($userId);

        if (! $user) {
            return redirect()->back()->withInput()->with('error', 'User not logged in or invalid user ID.');
        }

        $isAssistantMode = $this->request->getPost('assistant_mode') === '1';
        $this->userSettingsModel->where('user_id', $userId)->set(['assistant_mode_enabled' => $isAssistantMode])->update();

        $inputText = (string) $this->request->getPost('prompt');
        $uploadedFileIds = (array) $this->request->getPost('uploaded_media');

        // 1. Prepare context
        $contextData = $this->_prepareContext($userId, $inputText, $isAssistantMode);
        $finalPrompt = $contextData['finalPrompt'];
        
        // 2. Handle pre-uploaded files
        $uploadResult = $this->_handlePreUploadedFiles($uploadedFileIds, $userId);
        if (isset($uploadResult['error'])) {
            $this->_cleanupTempFiles($uploadedFileIds, $userId); // Clean up even on error
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

        // 3. Balance Check
        $balanceCheck = $this->_checkBalanceAgainstCost($user, $parts);
        if (isset($balanceCheck['error'])) {
            $this->_cleanupTempFiles($uploadedFileIds, $userId);
            return redirect()->back()->withInput()->with('error', $balanceCheck['error']);
        }

        // 4. Generate content
        $apiResponse = $this->geminiService->generateContent($parts);
        $this->_logApiPayload($apiResponse);
        
        // 5. Cleanup files immediately after API call
        $this->_cleanupTempFiles($uploadedFileIds, $userId);

        if (isset($apiResponse['error'])) {
            return redirect()->back()->withInput()->with('error', $apiResponse['error']);
        }

        // 6. Process API response and deduct cost
        $this->_processApiResponse($user, $apiResponse, $isAssistantMode, $contextData);

        $parsedown  = new Parsedown();
        $htmlResult = $parsedown->text($apiResponse['result']);

        return redirect()->back()->withInput()
            ->with('result', $htmlResult)
            ->with('raw_result', $apiResponse['result']);
    }

    /**
     * Prepares the final prompt, incorporating memory context if assistant mode is enabled.
     *
     * @param int    $userId          The ID of the current user.
     * @param string $inputText       The raw text input from the user.
     * @param bool   $isAssistantMode Whether assistant mode is active.
     *
     * @return array<string, mixed>
     */
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
    
    /**
     * Processes an array of pre-uploaded temporary file IDs from a user-specific directory.
     *
     * @param array $fileIds An array of sanitized temporary file names.
     * @param int $userId The ID of the current user.
     * @return array<string, mixed> An array containing the API 'parts' or an 'error'.
     */
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
    
    /**
     * Deletes temporary files from the specified user's directory.
     *
     * @param array $fileIds An array of sanitized temporary file names to delete.
     * @param int $userId The ID of the current user.
     * @return void
     */
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

    /**
     * Estimates input cost and checks if the user has sufficient balance.
     *
     * @param User  $user  The user entity.
     * @param array $parts The parts to be sent to the API.
     *
     * @return array<string, string> Returns an error array if balance is insufficient.
     */
    private function _checkBalanceAgainstCost(User $user, array $parts): array
    {
        $tokenCountResponse = $this->geminiService->countTokens($parts);

        if (! $tokenCountResponse['status']) {
            return ['error' => $tokenCountResponse['error']];
        }

        $inputTokens = $tokenCountResponse['totalTokens'];
        $costData    = $this->_calculateCost($inputTokens, 0); // Cost based on input only

        $requiredBalance = max(self::MINIMUM_BALANCE, $costData['costInKSH']);

        if (bccomp((string) $user->balance, (string) $requiredBalance, 2) < 0) {
            $errorMessage = "Insufficient balance. This query costs approx. KSH " . number_format($requiredBalance, 2) .
                            ", but you only have KSH " . $user->balance . ".";
            return ['error' => $errorMessage];
        }

        return [];
    }

    /**
     * Processes the API response, calculates final cost, deducts balance, and updates memory.
     *
     * @param User  $user          The user entity.
     * @param array $apiResponse   The response from the Gemini API.
     * @param bool  $isAssistantMode Whether assistant mode was active.
     * @param array $contextData   Context data from the prompt preparation step.
     *
     * @return void
     */
    private function _processApiResponse(User $user, array $apiResponse, bool $isAssistantMode, array $contextData): void
    {
        $inputTokens  = (int) ($apiResponse['usage']['promptTokenCount'] ?? 0);
        $outputTokens = (int) ($apiResponse['usage']['candidatesTokenCount'] ?? 0);

        $costData = $this->_calculateCost($inputTokens, $outputTokens);

        if ($this->userModel->deductBalance((int) $user->id, (string) $costData['deductionAmount'])) {
            session()->setFlashdata('success', $costData['costMessage']);
        } else {
            session()->setFlashdata('error', 'Query processed, but an error occurred during balance deduction.');
        }

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
    }

    /**
     * Calculates the cost of a query based on input and output tokens.
     *
     * @param int $inputTokens  The number of input tokens.
     * @param int $outputTokens The number of output tokens.
     *
     * @return array<string, mixed>
     */
    private function _calculateCost(int $inputTokens, int $outputTokens): array
    {
        if ($inputTokens === 0 && $outputTokens === 0) {
            return [
                'deductionAmount' => self::DEFAULT_DEDUCTION,
                'costMessage'     => "A default charge of KSH " . number_format(self::DEFAULT_DEDUCTION, 2) . " has been applied.",
                'costInKSH'       => self::DEFAULT_DEDUCTION,
            ];
        }

        // Pricing for Gemini 1.5 Pro
        $isTierOne = $inputTokens <= 128000;
        $inputPricePerMillion  = $isTierOne ? 3.50 : 7.00;
        $outputPricePerMillion = $isTierOne ? 10.50 : 21.00;

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

    /**
     * Logs the raw API payload to a file for debugging.
     *
     * @param array $apiResponse The API response.
     * @return void
     */
    private function _logApiPayload(array $apiResponse): void
    {
        $logFilePath = WRITEPATH . 'logs/gemini_payload.log';
        $logContent  = json_encode($apiResponse, JSON_PRETTY_PRINT);
        file_put_contents($logFilePath, $logContent . PHP_EOL, FILE_APPEND);
    }

    /**
     * Adds a new prompt for the logged-in user.
     *
     * @return RedirectResponse
     */
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

    /**
     * Deletes a specific prompt owned by the logged-in user.
     *
     * @param int $id The ID of the prompt to delete.
     *
     * @return RedirectResponse
     */
    public function deletePrompt(int $id): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return redirect()->to(url_to('gemini.index'))->with('error', 'You must be logged in.');
        }

        /** @var \App\Entities\Prompt|null $prompt */
        $prompt = $this->promptModel->find($id);

        if (! $prompt || (int) $prompt->user_id !== $userId) {
            return redirect()->to(url_to('gemini.index'))->with('error', 'You are not authorized to delete this prompt.');
        }

        if ($this->promptModel->delete($id)) {
            return redirect()->to(url_to('gemini.index'))->with('success', 'Prompt deleted successfully.');
        }

        return redirect()->to(url_to('gemini.index'))->with('error', 'Failed to delete the prompt.');
    }
}