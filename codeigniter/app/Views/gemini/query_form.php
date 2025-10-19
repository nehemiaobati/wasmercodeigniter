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
        // --- Main Form and Prompt Elements ---
        const geminiForm = document.getElementById('geminiForm');
        const mainPromptTextarea = document.getElementById('prompt');
        const submitButton = geminiForm.querySelector('button[type="submit"]');

        // --- Saved Prompts Elements ---
        const savedPromptsSelect = document.getElementById('savedPrompts');
        const usePromptBtn = document.getElementById('usePromptBtn');
        const deletePromptBtn = document.getElementById('deletePromptBtn');

        // --- Modal Elements ---
        const savePromptModal = new bootstrap.Modal(document.getElementById('savePromptModal'));
        const modalPromptTextarea = document.getElementById('modalPromptText');
        
        // --- Populate Save Modal ---
        const savePromptTrigger = document.querySelector('[data-bs-target="#savePromptModal"]');
        if (savePromptTrigger) {
            savePromptTrigger.addEventListener('click', () => {
                modalPromptTextarea.value = mainPromptTextarea.value;
            });
        }

        // --- Saved Prompts Logic ---
        if (savedPromptsSelect) {
            let selectedPromptId = null;

            savedPromptsSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                selectedPromptId = selectedOption.getAttribute('data-id');
                deletePromptBtn.disabled = !selectedPromptId;
            });

            if (usePromptBtn) {
                usePromptBtn.addEventListener('click', function() {
                    const selectedOption = savedPromptsSelect.options[savedPromptsSelect.selectedIndex];
                    if (selectedOption && selectedOption.value) {
                        mainPromptTextarea.value = selectedOption.value;
                    }
                });
            }

            if (deletePromptBtn) {
                deletePromptBtn.addEventListener('click', function() {
                    if (!selectedPromptId || this.disabled) return;
                    if (confirm('Are you sure you want to delete this prompt?')) {
                        const deleteUrl = `<?= rtrim(url_to('gemini.prompts.delete', 0), '0') ?>${selectedPromptId}`;
                        const tempForm = document.createElement('form');
                        tempForm.method = 'post';
                        tempForm.action = deleteUrl;
                        
                        const csrfField = geminiForm.querySelector('input[name="<?= csrf_token() ?>"]');
                        if(csrfField) {
                           tempForm.appendChild(csrfField.cloneNode());
                        }
                        document.body.appendChild(tempForm);
                        tempForm.submit();
                    }
                });
            }
        }

        // --- Media File Input Management ---
        const mediaUploadArea = document.getElementById('mediaUploadArea');
        const addMediaBtn = document.getElementById('addMediaBtn');
        const mediaContainer = document.getElementById('mediaInputContainer');
        const maxMediaFiles = 5;

        const updateMediaButtons = () => {
            const mediaRows = mediaContainer.querySelectorAll('.media-input-row');
            addMediaBtn.style.display = mediaRows.length < maxMediaFiles ? 'inline-block' : 'none';

            mediaRows.forEach((row, index) => {
                const removeBtn = row.querySelector('.remove-media-btn');
                removeBtn.style.display = (mediaRows.length > 1 || row.querySelector('input').files.length > 0) ? 'inline-block' : 'none';
            });
        };

        if (addMediaBtn && mediaContainer) {
            addMediaBtn.addEventListener('click', function() {
                if (mediaContainer.querySelectorAll('.media-input-row').length >= maxMediaFiles) return;
                const newRow = document.createElement('div');
                newRow.className = 'mb-2 media-input-row';
                newRow.innerHTML = `<input type="file" class="form-control" name="media[]"><button type="button" class="btn btn-outline-danger btn-sm remove-media-btn"><i class="bi bi-x-lg"></i></button>`;
                mediaContainer.appendChild(newRow);
                updateMediaButtons();
            });

            mediaContainer.addEventListener('click', e => {
                if (e.target.closest('.remove-media-btn')) {
                    e.target.closest('.media-input-row').remove();
                    updateMediaButtons();
                }
            });
            mediaContainer.addEventListener('change', e => {
                if (e.target.matches('input[type="file"]')) updateMediaButtons();
            });

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => mediaUploadArea.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); }));
            ['dragenter', 'dragover'].forEach(eventName => mediaUploadArea.addEventListener(eventName, () => mediaUploadArea.classList.add('dragover')));
            ['dragleave', 'drop'].forEach(eventName => mediaUploadArea.addEventListener(eventName, () => mediaUploadArea.classList.remove('dragover')));
            
            mediaUploadArea.addEventListener('drop', e => {
                const firstInput = mediaContainer.querySelector('input[type="file"]');
                if (firstInput && firstInput.files.length === 0) {
                    firstInput.files = e.dataTransfer.files;
                    firstInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            updateMediaButtons();
        }

        // --- Form Submission Loading State ---
        if (geminiForm && submitButton) {
            geminiForm.addEventListener('submit', function() {
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';
                submitButton.disabled = true;
            });
        }
        
        // --- AI Response and Copy Logic ---
        const responseWrapper = document.getElementById('ai-response-wrapper');
        const copyBtn = document.getElementById('copy-response-btn');

        if (responseWrapper && copyBtn) {
            const rawTextarea = document.getElementById('raw-response-for-copy');
            const resultsCard = responseWrapper.closest('.results-card');

            setTimeout(() => {
                resultsCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);

            copyBtn.addEventListener('click', function() {
                navigator.clipboard.writeText(rawTextarea.value).then(() => {
                    const originalIcon = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
                    setTimeout(() => { this.innerHTML = originalIcon; }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            });
        }

        // --- AJAX handler for Assistant Mode Toggle ---
        const assistantModeToggle = document.getElementById('assistantModeToggle');
        const csrfToken = document.querySelector('input[name="<?= csrf_token() ?>"]').value; // Get CSRF token

        if (assistantModeToggle) {
            assistantModeToggle.addEventListener('change', async function() {
                const isChecked = this.checked;
                const url = `<?= url_to('gemini.settings.update_assistant_mode') ?>`;

                try {
                    const response = await fetch(url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ assistant_mode: isChecked })
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        console.error('Failed to update assistant mode:', errorData.message || response.statusText);
                        // Optionally, revert the toggle or show an error message to the user
                        // For now, just log to console
                    } else {
                        const successData = await response.json();
                        console.log('Assistant mode updated:', successData.message);
                        // Optionally, provide user feedback here
                    }
                } catch (error) {
                    console.error('Error updating assistant mode:', error);
                    // Handle network errors
                }
            });
        }
    });
</script>
<?= $this->endSection() ?>
