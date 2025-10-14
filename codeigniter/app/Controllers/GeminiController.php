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
        
        /** @var User|null $user */
        $user = $this->userModel->find($userId);
        $deductionAmount = 10;

        if (!$user || $user->balance < $deductionAmount) {
            return redirect()->back()->withInput()->with('error', ['Insufficient balance. Please top up your account.']);
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

        $response = $this->geminiService->generateContent($parts);

        if (isset($response['error'])) {
            return redirect()->back()->withInput()->with('error', ['error' => $response['error']]);
        }

        if ($this->userModel->deductBalance($userId, $deductionAmount)) {
            session()->setFlashdata('success', "{$deductionAmount} units deducted for your AI query.");
        } else {
            log_message('error', 'Failed to update user balance after successful AI query for user ID: ' . $userId);
        }

        return redirect()->back()->withInput()->with('result', $response['result']);
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
