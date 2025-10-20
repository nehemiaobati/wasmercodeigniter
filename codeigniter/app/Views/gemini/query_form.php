<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
    <!-- ADD THIS LINE FOR SYNTAX HIGHLIGHTING STYLES -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css">
<style>
    .query-card,
    .results-card,
    .settings-card {
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        border: none;
        transition: all 0.3s ease-in-out;
        height: 100%; /* Make cards in the same row equal height */
    }

    .results-card pre {
        background-color: #f8f9fa;
        padding: 1.5rem;
        border-radius: 0.5rem;
        white-space: pre-wrap;
        word-wrap: break-word;
        border: 1px solid #dee2e6;
        min-height: 100px; /* Ensure pre has height for the cursor */
    }

    /* Settings Card specific styles */
    .settings-card .card-body {
        display: flex;
        flex-direction: column;
    }
    .settings-card .form-check-label {
        font-weight: 500;
    }
    .settings-card .saved-prompts-block {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e9ecef;
    }

    /* Media Upload Area Styling */
    #mediaUploadArea {
        border: 2px dashed #ced4da;
        border-radius: 0.5rem;
        padding: 1.5rem;
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }

    #mediaUploadArea.dragover {
        background-color: #e9ecef;
        border-color: var(--primary-color);
    }

    .media-input-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        animation: fadeIn 0.3s ease-in-out;
    }

    .media-input-row .form-control {
        flex-grow: 1;
    }

    /* Typing cursor animation */
    #ai-response-content.typing::after {
        content: '▋';
        display: inline-block;
        animation: blink 1s step-end infinite;
    }

    @keyframes blink {
        from, to { color: transparent; }
        50% { color: var(--primary-color); }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Styles for rendered HTML content */
    .ai-response-html { line-height: 1.6; color: #333; }
    .ai-response-html h1, .ai-response-html h2, .ai-response-html h3, .ai-response-html h4 {
        margin-top: 1.5em; margin-bottom: 0.8em; font-weight: 600;
    }
    .ai-response-html h1 { font-size: 2em; }
    .ai-response-html h2 { font-size: 1.75em; }
    .ai-response-html h3 { font-size: 1.5em; }
    .ai-response-html p { margin-bottom: 1em; }
    .ai-response-html ul, .ai-response-html ol { margin-bottom: 1em; padding-left: 2em; }
    .ai-response-html li { margin-bottom: 0.5em; }
    .ai-response-html pre {
        background-color: #f8f9fa; padding: 1rem; border-radius: 0.5rem;
        overflow-x: auto; border: 1px solid #dee2e6; margin-bottom: 1em;
        font-family: 'Courier New', Courier, monospace; font-size: 0.9em; line-height: 1.4;
    }
    .ai-response-html code {
        font-family: 'Courier New', Courier, monospace; background-color: rgba(0, 0, 0, 0.05);
        padding: 0.2em 0.4em; border-radius: 0.3em; font-size: 0.9em;
    }
    .ai-response-html pre code { background-color: transparent; padding: 0; font-size: inherit; }
</style>

    <!-- ADD THIS LINE TO LOAD THE HIGHLIGHT.JS LIBRARY -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

    <!-- This line (which you already have) can now run successfully -->
    <script>hljs.highlightAll();</script> 
    
    <?= $this->renderSection('scripts') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold"><i class="bi bi-stars text-primary"></i> Gemini AI Studio</h1>
        <p class="text-muted lead">Craft your prompts, attach media, and generate content with assistant-level context.</p>
    </div>

    <form id="geminiForm" action="<?= url_to('gemini.generate') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row g-4 justify-content-center">
            <!-- Left Column: Settings & Config -->
            <div class="col-lg-4">
                <div class="card settings-card">
                    <div class="card-body p-4">
                        <h4 class="card-title fw-bold mb-4">
                            <i class="bi bi-gear-fill"></i> Settings
                        </h4>
                        <div class="form-check form-switch fs-5 p-0 d-flex justify-content-between align-items-center">
                            <label class="form-check-label" for="assistantModeToggle">Assistant Mode</label>
                            <input class="form-check-input" type="checkbox" role="switch" id="assistantModeToggle" name="assistant_mode" value="1" <?= old('assistant_mode', $assistant_mode_enabled ? '1' : '0') === '1' ? 'checked' : '' ?>>
                        </div>
                        <small class="text-muted d-block mt-1">Enables memory and context for conversational queries.</small>
                        
                        <?php if (!empty($prompts)): ?>
                        <div class="saved-prompts-block flex-grow-1">
                            <label for="savedPrompts" class="form-label fw-bold">Saved Prompts</label>
                            <div class="input-group">
                                <select class="form-select" id="savedPrompts">
                                    <option selected disabled>Select a prompt...</option>
                                    <?php foreach ($prompts as $p): ?>
                                        <option value="<?= esc($p->prompt_text) ?>" data-id="<?= $p->id ?>"><?= esc($p->title) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" id="usePromptBtn" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-arrow-down-circle"></i> Use</button>
                                <button type="button" id="deletePromptBtn" class="btn btn-sm btn-outline-danger w-100" disabled><i class="bi bi-trash"></i> Delete</button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Main Prompt & Actions -->
            <div class="col-lg-8">
                <div class="card query-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="form-floating mb-2">
                            <textarea id="prompt" name="prompt" class="form-control" placeholder="Enter your prompt" style="height: 150px" required><?= old('prompt') ?></textarea>
                            <label for="prompt">Your Prompt</label>
                        </div>
                        <div class="d-flex justify-content-end mb-4">
                            <button type="button" class="btn btn-link text-decoration-none btn-sm" data-bs-toggle="modal" data-bs-target="#savePromptModal">
                                <i class="bi bi-bookmark-plus"></i> Save this prompt
                            </button>
                        </div>

                        <div id="mediaUploadArea" class="mb-4">
                            <p class="text-muted text-center mb-3"><i class="bi bi-paperclip"></i> Attach files (optional)</p>
                            <div id="mediaInputContainer">
                                <div class="mb-2 media-input-row">
                                    <input type="file" class="form-control" name="media[]">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-media-btn" style="display: none;"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <button type="button" id="addMediaBtn" class="btn btn-secondary btn-sm"><i class="bi bi-plus-circle"></i> Add File</button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold"><i class="bi bi-robot"></i> Generate Content</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <?php 
        $result = session()->getFlashdata('result');
        $raw_result = session()->getFlashdata('raw_result');
        if ($result):
    ?>
        <div class="row justify-content-center mt-4">
            <div class="col-lg-12">
                <div class="card results-card">
                    <div class="card-body p-4 p-md-5">
                        <h3 class="fw-bold mb-4 d-flex justify-content-between align-items-center">
                            AI Response
                            <button id="copy-response-btn" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </h3>
                        <div id="ai-response-wrapper" class="ai-response-html">
                             <?= $result ?>
                        </div>
                        <textarea id="raw-response-for-copy" class="visually-hidden"><?= esc($raw_result ?? strip_tags($result)) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Save Prompt Modal -->
<div class="modal fade" id="savePromptModal" tabindex="-1" aria-labelledby="savePromptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="savePromptModalLabel">Save New Prompt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url_to('gemini.prompts.add') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="promptTitle" name="title" placeholder="Prompt Title" required>
                        <label for="promptTitle">Prompt Title</label>
                    </div>
                    <div class="form-floating">
                        <textarea class="form-control" placeholder="Prompt Text" id="modalPromptText" name="prompt_text" style="height: 100px" required></textarea>
                        <label for="modalPromptText">Prompt Text</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Prompt</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ... (other javascript before this block remains unchanged) ...

        // --- AJAX handler for Assistant Mode Toggle ---
        const assistantModeToggle = document.getElementById('assistantModeToggle');
        const csrfToken = document.querySelector('input[name="<?= csrf_token() ?>"]').value;

        if (assistantModeToggle) {
            assistantModeToggle.addEventListener('change', async function() {
                const isChecked = this.checked;
                // THIS IS THE CHANGE: Build the URL dynamically to ensure same-origin requests.
                const url = window.location.origin + `<?= route_to('gemini.settings.update_assistant_mode') ?>`;

                try {
                    const response = await fetch(url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ assistant_mode: isChecked })
                    });

                    const responseData = await response.json();

                    if (!response.ok) {
                        console.error('Failed to update assistant mode:', responseData.message || response.statusText);
                    } else {
                        console.log('Assistant mode updated:', responseData.message);
                    }
                } catch (error) {
                    console.error('Error updating assistant mode:', error);
                }
            });
        }
    });
</script>
<?= $this->endSection() ?>