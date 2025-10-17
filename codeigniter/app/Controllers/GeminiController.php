<?php declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\PromptModel;
use App\Libraries\GeminiService;
use CodeIgniter\HTTP\RedirectResponse;
use App\Entities\User; // Import the User entity

class GeminiController extends BaseController
{
    protected UserModel $userModel;
    protected GeminiService $geminiService;
    protected PromptModel $promptModel;

    /**
     * Constructor.
     * Initializes the UserModel and GeminiService via the services container.
     */
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->geminiService = service('geminiService');
        $this->promptModel = new PromptModel();
    }

    public function index(): string
    {
        $userId = (int) session()->get('userId');
        $prompts = $this->promptModel->where('user_id', $userId)->findAll();

        $data = [
            'title'   => 'Gemini AI Query',
            'result'  => session()->getFlashdata('result'),
            'error'   => session()->getFlashdata('error'),
            'prompts' => $prompts,
        ];
        return view('gemini/query_form', $data);
    }

    public function generate(): RedirectResponse
    {
        $userId = (int) session()->get('userId'); // Cast userId to integer
        if ($userId <= 0) {
            return redirect()->back()->withInput()->with('error', ['User not logged in or invalid user ID. Cannot deduct balance.']);
        }
        
        $inputText = $this->request->getPost('prompt');
        $isReport = $this->request->getPost('report') === '1';

        if ($isReport) {
            $inputText = "no markdown\n\n{$inputText}";
        }
        $uploadedFiles = $this->request->getFileMultiple('media') ?: [];

        $supportedMimeTypes = [
            'image/png', 'image/jpeg', 'image/webp',
            'audio/mpeg', 'audio/mp3', 'audio/wav',
            'video/mov', 'video/mpeg', 'video/mp4', 'video/mpg', 'video/avi', 'video/wmv', 'video/mpegps', 'video/flv',
            'application/pdf',
            'text/plain'
        ];

        $parts = [];
        if ($inputText) {
            $parts[] = ['text' => $inputText];
        }

        $maxFileSize = 10 * 1024 * 1024;

        if (!empty($uploadedFiles)) {
            foreach ($uploadedFiles as $file) {
                if ($file->isValid()) {
                    $mimeType = $file->getMimeType();

                    if (!in_array($mimeType, $supportedMimeTypes)) {
                        return redirect()->back()->withInput()->with('error', ["Unsupported file type: {$mimeType}. Please upload only supported media types."]);
                    }

                    if ($file->getSize() > $maxFileSize) {
                        return redirect()->back()->withInput()->with('error', ['Uploaded file is too large. Maximum allowed size is 10 MB.']);
                    }

                    $filePath = $file->getTempName();
                    $fileContent = file_get_contents($filePath);
                    $base64Content = base64_encode($fileContent);

                    $parts[] = [
                        'inlineData' => [
                            'mimeType' => $mimeType,
                            'data' => $base64Content
                        ]
                    ];
                }
            }
        }

        if (empty($parts)) {
            return redirect()->back()->withInput()->with('error', ['Prompt or supported media is required.']);
        }

        $apiResponse = $this->geminiService->generateContent($parts);

        if (isset($apiResponse['error'])) {
            return redirect()->back()->withInput()->with('error', ['error' => $apiResponse['error']]);
        }

        // --- Token-based Pricing Logic in KSH ---
        $deductionAmount = 10.00; // Default fallback cost in KSH
        $costMessage = "A default charge of KSH " . number_format($deductionAmount, 2) . " has been applied for your AI query.";
        define('USD_TO_KSH_RATE', 129);

        // Check if usage metadata is available for precise cost calculation
        if (isset($apiResponse['usage']['totalTokenCount'], $apiResponse['usage']['promptTokenCount'], $apiResponse['usage']['candidatesTokenCount'])) {
            $totalTokens = (int) $apiResponse['usage']['totalTokenCount'];
            $inputTokens = (int) $apiResponse['usage']['promptTokenCount'];
            $outputTokens = (int) $apiResponse['usage']['candidatesTokenCount'];

            $inputPricePerMillion = 0.0;
            $outputPricePerMillion = 0.0;
            
            // Pricing for gemini-2.5-pro model
            if ($totalTokens <= 200000) { // <= 200K tokens pricing tier
                $inputPricePerMillion = 3.25;  // $1.25 per 1,000,000 tokens
                $outputPricePerMillion = 12.00; // $10.00 per 1,000,000 tokens
            } else { // > 200K tokens pricing tier
                $inputPricePerMillion = 2.50;  // $2.50 per 1,000,000 tokens
                $outputPricePerMillion = 17.00; // $15.00 per 1,000,000 tokens
            }

            $inputCostUSD = ($inputTokens / 1000000) * $inputPricePerMillion;
            $outputCostUSD = ($outputTokens / 1000000) * $outputPricePerMillion;
            $totalCostUSD = $inputCostUSD + $outputCostUSD;
            
            // Convert USD cost to KSH
            $costInKSH = $totalCostUSD * USD_TO_KSH_RATE;
            
            // Ensure a minimum charge of KES 0.01 for any successful API call with usage data.
            $deductionAmount = max(0.01, $costInKSH);
            $costMessage = "KSH " . number_format($deductionAmount, 2) . " deducted for your AI query based on token usage.";
        }
        
        /** @var User|null $user */
        $user = $this->userModel->find($userId);

        // Check if the user has enough balance for the calculated cost
        if (bccomp((string) $user->balance, (string) $deductionAmount, 2) < 0) {
            log_message('error', "User {$userId} had insufficient balance for a query that already ran. Cost: {$deductionAmount}, Balance: {$user->balance}.");
            session()->setFlashdata('error', 'Your query was processed, but your balance was insufficient to cover the full cost. Please top up your account.');
            return redirect()->back()->withInput()->with('result', $apiResponse['result']);
        }

        // Deduct the calculated KSH amount from the user's balance
        $newBalance = bcsub((string) $user->balance, (string) $deductionAmount, 2);
        if ($this->userModel->update($userId, ['balance' => $newBalance])) {
            session()->setFlashdata('success', $costMessage);
        } else {
            log_message('error', 'Failed to update user balance for user ID: ' . $userId . '. Deduction amount was: KSH ' . $deductionAmount);
            session()->setFlashdata('error', 'Could not update your balance after the query. Please contact support.');
        }

        return redirect()->back()->withInput()->with('result', $apiResponse['result']);
    }

    public function addPrompt(): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return redirect()->to(url_to('gemini.index'))->with('error', 'You must be logged in to add a prompt.');
        }

        $validation = $this->validate([
            'title'       => 'required|max_length[255]',
            'prompt_text' => 'required',
        ]);

        if (!$validation) {
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

    public function deletePrompt($id): RedirectResponse
    {
        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return redirect()->to(url_to('gemini.index'))->with('error', 'You must be logged in to delete a prompt.');
        }

        $prompt = $this->promptModel->find($id);

        if (!$prompt || (int) $prompt->user_id !== $userId) {
            return redirect()->to(url_to('gemini.index'))->with('error', 'You are not authorized to delete this prompt.');
        }

        if ($this->promptModel->delete($id)) {
            return redirect()->to(url_to('gemini.index'))->with('success', 'Prompt deleted successfully.');
        }

        return redirect()->to(url_to('gemini.index'))->with('error', 'Failed to delete the prompt.');
    }
}