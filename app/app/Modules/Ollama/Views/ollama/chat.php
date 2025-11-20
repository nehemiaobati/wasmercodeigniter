<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .think-block {
        background-color: #f8f9fa;
        border-left: 4px solid #6c757d;
        padding: 1rem;
        margin-bottom: 1rem;
        font-style: italic;
        color: #555;
        border-radius: 0.25rem;
    }
    .think-label {
        font-weight: bold;
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #adb5bd;
        margin-bottom: 0.5rem;
    }
    .chat-bubble {
        padding: 1.5rem;
        border-radius: 1rem;
        margin-bottom: 1.5rem;
    }
    .chat-user {
        background-color: var(--bs-primary-bg-subtle);
        border: 1px solid var(--bs-primary-border-subtle);
        margin-left: 2rem;
    }
    .chat-ai {
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        margin-right: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
</style>
<link rel="stylesheet" href="<?= base_url('assets/highlight/styles/github-dark.min.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="blueprint-header text-center mb-4">
        <div class="d-flex justify-content-center align-items-center gap-3">
            <h1 class="fw-bold mb-0">Local AI Studio</h1>
            <?php if($isOnline): ?>
                <span class="badge bg-success">Online</span>
            <?php else: ?>
                <span class="badge bg-danger">Offline</span>
            <?php endif; ?>
        </div>
        <p class="text-muted mt-2">Powered by Ollama & DeepSeek R1</p>
    </div>

    <div class="row g-4">
        <!-- Main Chat Area -->
        <div class="col-lg-8">
            <!-- New Response Display -->
            <?php if (session()->getFlashdata('success_response')): ?>
                <div class="mb-4">
                    <h5 class="fw-bold text-primary"><i class="bi bi-sparkles"></i> New Response</h5>
                    <div class="chat-bubble chat-ai" id="latest-response">
                        <!-- Content injected via JS for correct formatting -->
                    </div>
                </div>
            <?php endif; ?>

            <!-- Input Form -->
            <div class="blueprint-card p-4 mb-5">
                <form action="<?= url_to('ollama.chat') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="prompt" class="form-label fw-bold">Ask DeepSeek</label>
                        <textarea class="form-control" id="prompt" name="prompt" rows="3" placeholder="Why is the sky blue? Explain your reasoning..." required><?= old('prompt') ?></textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary" <?= !$isOnline ? 'disabled' : '' ?>>
                            <i class="bi bi-send"></i> Send Query
                        </button>
                    </div>
                </form>
            </div>

            <!-- History -->
            <?php if (!empty($history)): ?>
                <h5 class="fw-bold mb-3 text-muted">Recent History</h5>
                <?php foreach ($history as $chat): ?>
                    <div class="mb-4">
                        <div class="chat-bubble chat-user">
                            <strong>You:</strong> <?= esc($chat->user_input) ?>
                        </div>
                        <div class="chat-bubble chat-ai markdown-content">
                            <?= esc($chat->ai_response) ?> 
                            <!-- Note: We escape here, but JS below handles the rendering/un-escaping safely -->
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="blueprint-card p-4">
                <h5 class="fw-bold">Model Info</h5>
                <p class="text-muted small">Running <code>deepseek-r1:1.5b</code> locally via Ollama.</p>
                <hr>
                <form action="<?= url_to('ollama.clear') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                        <i class="bi bi-trash"></i> Clear Memory
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Hidden raw data for the latest response -->
<?php if ($raw = session()->getFlashdata('success_response')): ?>
<script id="raw-response-data" type="text/plain"><?= $raw ?></script>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="<?= base_url('assets/highlight/highlight.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        
        function formatDeepSeek(text) {
            // 1. Extract <think> blocks
            const thinkRegex = /<think>([\s\S]*?)<\/think>/g;
            let formatted = text.replace(thinkRegex, (match, content) => {
                return `<div class="think-block"><div class="think-label">Reasoning</div>${marked.parse(content)}</div>`;
            });
            
            // 2. Parse the rest as Markdown
            return marked.parse(formatted);
        }

        // Render Latest Response
        const rawScript = document.getElementById('raw-response-data');
        const latestContainer = document.getElementById('latest-response');
        
        if (rawScript && latestContainer) {
            latestContainer.innerHTML = formatDeepSeek(rawScript.textContent);
            hljs.highlightAll();
        }

        // Render History (Simplified for demo, ideally done server-side or purely via JS)
        document.querySelectorAll('.markdown-content').forEach(el => {
            const rawText = el.innerText; // Get escaped text
            el.innerHTML = formatDeepSeek(rawText);
        });
        
        hljs.highlightAll();
    });
</script>
<?= $this->endSection() ?>
