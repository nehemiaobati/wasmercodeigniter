<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .query-card,
    .results-card {
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        border: none;
        transition: all 0.3s ease-in-out;
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

    /* Saved Prompts Enhancement */
    .saved-prompts-block {
        background-color: #f8f9fa;
        border: 1px dashed #ced4da;
        border-radius: 0.5rem;
        padding: 1rem;
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
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card query-card">
                <div class="card-body p-4 p-md-5">
                    <form id="geminiForm" action="<?= url_to('gemini.generate') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="card-title fw-bold mb-0"><i class="bi bi-stars text-primary"></i> Gemini AI</h2>
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" role="switch" id="reportToggle" name="report" value="1">
                                <label class="form-check-label" for="reportToggle">Report Mode</label>
                            </div>
                        </div>

                        <?php if (!empty($prompts)): ?>
                            <div class="saved-prompts-block mb-4">
                                <label for="savedPrompts" class="form-label fw-bold">Use a Saved Prompt</label>
                                <div class="input-group">
                                    <select class="form-select" id="savedPrompts">
                                        <option selected disabled>Select a prompt...</option>
                                        <?php foreach ($prompts as $p): ?>
                                            <option value="<?= esc($p->prompt_text) ?>" data-id="<?= $p->id ?>"><?= esc($p->title) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" id="usePromptBtn" class="btn btn-outline-secondary"><i class="bi bi-arrow-down-circle"></i> Use</button>
                                    <button type="button" id="deletePromptBtn" class="btn btn-outline-danger" disabled><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-floating mb-2">
                            <textarea id="prompt" name="prompt" class="form-control" placeholder="Enter your prompt" style="height: 125px" required><?= old('prompt') ?></textarea>
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
                    </form>
                </div>
            </div>
        </div>

        <?php if ($result = session()->getFlashdata('result')): ?>
            <div class="col-lg-8">
                <div class="card results-card mt-4">
                    <div class="card-body p-4 p-md-5">
                        <h3 class="fw-bold mb-4 d-flex justify-content-between align-items-center">
                            AI Response
                            <button id="copy-response-btn" class="btn btn-sm btn-outline-secondary" disabled>
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </h3>
                        <pre id="ai-response-content" data-full-text="<?= esc($result) ?>"></pre>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
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
                        <textarea class="form-control" placeholder="Prompt Text" id="promptText" name="prompt_text" style="height: 100px" required></textarea>
                        <label for="promptText">Prompt Text</label>
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
        if (mainPromptTextarea) {
            mainPromptTextarea.focus({ preventScroll: true });
        }
        const submitButton = geminiForm.querySelector('button[type="submit"]');

        // --- Saved Prompts Elements ---
        const savedPromptsSelect = document.getElementById('savedPrompts');
        const usePromptBtn = document.getElementById('usePromptBtn');
        const deletePromptBtn = document.getElementById('deletePromptBtn');

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
                        const savedPrompt = selectedOption.value;
                        const existingPrompt = mainPromptTextarea.value.trim();

                        if (existingPrompt) {
                            mainPromptTextarea.value = savedPrompt + '\n\n' + existingPrompt;
                        } else {
                            mainPromptTextarea.value = savedPrompt;
                        }
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
                removeBtn.style.display = (mediaRows.length > 1) ? 'inline-block' : 'none';
            });
             // Also hide the first remove button if there is only one empty input
            if (mediaRows.length === 1) {
                const firstInput = mediaRows[0].querySelector('input[type="file"]');
                const firstRemoveBtn = mediaRows[0].querySelector('.remove-media-btn');
                if (firstInput.files.length === 0) {
                    firstRemoveBtn.style.display = 'none';
                }
            }
        };

        if (addMediaBtn && mediaContainer) {
            addMediaBtn.addEventListener('click', function() {
                const newRow = document.createElement('div');
                newRow.className = 'mb-2 media-input-row';
                newRow.innerHTML = `
                    <input type="file" class="form-control" name="media[]">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-media-btn"><i class="bi bi-x-lg"></i></button>
                `;
                mediaContainer.appendChild(newRow);
                updateMediaButtons();
            });

            mediaContainer.addEventListener('click', function(event) {
                const removeBtn = event.target.closest('.remove-media-btn');
                if (removeBtn) {
                    removeBtn.closest('.media-input-row').remove();
                    updateMediaButtons();
                }
            });
             mediaContainer.addEventListener('change', function(event) {
                if (event.target.matches('input[type="file"]')) {
                    const removeBtn = event.target.closest('.media-input-row').querySelector('.remove-media-btn');
                    if (event.target.files.length > 0) {
                        removeBtn.style.display = 'inline-block';
                    }
                    updateMediaButtons();
                }
            });

            // Optional: Drag and drop listeners for better UX
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                mediaUploadArea.addEventListener(eventName, e => {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });
            ['dragenter', 'dragover'].forEach(eventName => {
                mediaUploadArea.addEventListener(eventName, () => mediaUploadArea.classList.add('dragover'), false);
            });
            ['dragleave', 'drop'].forEach(eventName => {
                mediaUploadArea.addEventListener(eventName, () => mediaUploadArea.classList.remove('dragover'), false);
            });
            mediaUploadArea.addEventListener('drop', e => {
                 const firstInput = mediaContainer.querySelector('input[type="file"]');
                 if(firstInput.files.length === 0) { // If the first input is empty, use it
                    firstInput.files = e.dataTransfer.files;
                    const changeEvent = new Event('change', { bubbles: true });
                    firstInput.dispatchEvent(changeEvent);
                 }
            }, false);

            updateMediaButtons();
        }

        // --- Form Submission Loading State ---
        if (geminiForm && submitButton) {
            geminiForm.addEventListener('submit', function(event) {
                if (this.action.includes('delete')) return;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';
                submitButton.disabled = true;
            });
        }

        // --- AI Response Streaming ---
        const responseElement = document.getElementById('ai-response-content');
        if (responseElement) {
            const resultsCard = responseElement.closest('.results-card');
            const copyBtn = document.getElementById('copy-response-btn');
            const fullText = responseElement.getAttribute('data-full-text');
            responseElement.removeAttribute('data-full-text'); // Clean up

            // 1. Scroll to the results card
            setTimeout(() => {
                resultsCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);

            // 2. Artificially stream the response
            let index = 0;
            responseElement.classList.add('typing');
            
            function type() {
                if (index < fullText.length) {
                    responseElement.textContent += fullText.charAt(index);
                    index++;
                    setTimeout(type, 10); // Adjust speed here (milliseconds)
                } else {
                    responseElement.classList.remove('typing');
                    if (copyBtn) copyBtn.disabled = false; // Enable copy button when done
                }
            }
            
            setTimeout(type, 500); // Start typing after a short delay

            // --- Copy to Clipboard Functionality ---
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    navigator.clipboard.writeText(fullText).then(() => {
                        const originalHtml = copyBtn.innerHTML;
                        copyBtn.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
                        setTimeout(() => { copyBtn.innerHTML = originalHtml; }, 2000);
                    }).catch(err => console.error('Failed to copy text: ', err));
                });
            }
        }
    });
</script>
<?= $this->endSection() ?>