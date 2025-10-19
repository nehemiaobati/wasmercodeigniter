<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Libraries\GeminiService;
use App\Libraries\MemoryService;
use App\Models\PromptModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
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
     * Maximum total size for all uploaded files (50 MB).
     * @var int
     */
    private const TOTAL_MAX_FILE_SIZE = 50 * 1024 * 1024;

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
        $this->userModel     = new UserModel();
        $this->geminiService = service('geminiService');
        $this->promptModel   = new PromptModel();
    }

    /**
     * Displays the main query form with user's saved prompts.
     *
     * @return string The rendered view.
     */
    public function index(): string
    {
        $userId  = (int) session()->get('userId');
        $prompts = $this->promptModel->where('user_id', $userId)->findAll();

        $data = [
            'title'   => 'Gemini AI Query',
            'result'  => session()->getFlashdata('result'),
            'error'   => session()->getFlashdata('error'),
            'prompts' => $prompts,
        ];
        return view('gemini/query_form', $data);
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

        $inputText       = (string) $this->request->getPost('prompt');
        $isAssistantMode = $this->request->getPost('assistant_mode') === '1';

        // 1. Prepare the prompt and context
        $contextData = $this->_prepareContext($userId, $inputText, $isAssistantMode);
        $finalPrompt = $contextData['finalPrompt'];

        // 2. Handle file uploads
        $uploadResult = $this->_handleFileUploads();
        if (isset($uploadResult['error'])) {
            return redirect()->back()->withInput()->with('error', $uploadResult['error']);
        }
        $parts = $uploadResult['parts'];

        if ($finalPrompt) {
            array_unshift($parts, ['text' => $finalPrompt]);
        }

        if (empty($parts)) {
            return redirect()->back()->withInput()->with('error', 'Prompt or supported media is required.');
        }

        // 3. Check user balance against estimated cost
        $balanceCheck = $this->_checkBalanceAgainstCost($user, $parts);
        if (isset($balanceCheck['error'])) {
            return redirect()->back()->withInput()->with('error', $balanceCheck['error']);
        }

        // 4. Generate content via API
        $apiResponse = $this->geminiService->generateContent($parts);
        $this->_logApiPayload($apiResponse);

        if (isset($apiResponse['error'])) {
            return redirect()->back()->withInput()->with('error', $apiResponse['error']);
        }

        // 5. Process the API response and deduct cost
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
     * Handles and validates file uploads from the request.
     *
     * @return array<string, mixed>
     */
    private function _handleFileUploads(): array
    {
        $uploadedFiles = $this->request->getFileMultiple('media') ?: [];
        $totalFileSize = 0;
        $parts         = [];

        foreach ($uploadedFiles as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $mimeType = $file->getMimeType();
            if (! in_array($mimeType, self::SUPPORTED_MIME_TYPES, true)) {
                return ['error' => "Unsupported file type: {$mimeType}."];
            }

            if ($file->getSize() > self::MAX_FILE_SIZE) {
                return ['error' => 'A file exceeds the 10 MB size limit.'];
            }

            $totalFileSize += $file->getSize();
            if ($totalFileSize > self::TOTAL_MAX_FILE_SIZE) {
                return ['error' => 'Total file size exceeds the 50 MB limit.'];
            }

            $base64Content = base64_encode(file_get_contents($file->getTempName()));
            $parts[]       = ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64Content]];
        }

        return ['parts' => $parts];
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

        // Pricing for Gemini 2.5 Pro
        $isTierOne = $inputTokens <= 200000;
        $inputPricePerMillion  = $isTierOne ? 3.25 : 4.50;
        $outputPricePerMillion = $isTierOne ? 12.00 : 17.00;

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
