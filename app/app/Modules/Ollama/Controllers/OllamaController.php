<?php declare(strict_types=1);

namespace App\Modules\Ollama\Controllers;

use App\Controllers\BaseController;
use App\Modules\Ollama\Libraries\OllamaService;
use App\Modules\Ollama\Libraries\OllamaMemoryService;
use App\Modules\Ollama\Models\OllamaEntityModel;
use App\Modules\Ollama\Models\OllamaInteractionModel;

class OllamaController extends BaseController
{
    private OllamaService $apiService;

    public function __construct()
    {
        $this->apiService = new OllamaService();
    }

    public function index(): string
    {
        $userId = (int) session()->get('userId');
        
        // Fetch history directly for view presentation
        $history = model(OllamaInteractionModel::class)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->findAll();

        $isOnline = $this->apiService->isOnline();
        if (!$isOnline) {
            session()->setFlashdata('error', 'Local Ollama service is unreachable.');
        }

        return view('App\Modules\Ollama\Views\ollama\chat', [
            'pageTitle'    => 'Local AI | Ollama & DeepSeek',
            'isOnline'     => $isOnline,
            'history'      => $history,
            'canonicalUrl' => url_to('ollama.index')
        ]);
    }

    public function chat()
    {
        $prompt = trim((string) $this->request->getPost('prompt'));

        if ($prompt === '') {
            return redirect()->back()->with('error', 'Please enter a prompt.');
        }

        if (!$this->apiService->isOnline()) {
            return redirect()->back()->withInput()->with('error', 'Ollama service is offline.');
        }

        // Delegate complex logic to Memory Service
        $userId = (int) session()->get('userId');
        $memory = new OllamaMemoryService($userId);
        
        $result = $memory->processChat($prompt);

        if (!$result['success']) {
            return redirect()->back()->withInput()->with('error', $result['error']);
        }

        // Return raw response. The View (JS) handles the Markdown/<think> rendering.
        return redirect()->back()->with('success_response', $result['response']);
    }

    public function clearHistory()
    {
        $userId = (int) session()->get('userId');
        model(OllamaInteractionModel::class)->where('user_id', $userId)->delete();
        model(OllamaEntityModel::class)->where('user_id', $userId)->delete();
        
        return redirect()->back()->with('success', 'Conversation history cleared.');
    }
}