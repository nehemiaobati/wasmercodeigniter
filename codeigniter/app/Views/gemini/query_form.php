<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .query-card,
    .results-card {
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        border: none;
    }

    .results-card pre {
        background-color: #f8f9fa;
        padding: 1.5rem;
        border-radius: 0.5rem;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .file-input-group {
        display: flex;
        gap: 0.5rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card query-card">
                <div class="card-body p-4 p-md-5">
                    <form id="geminiForm" action="<?= url_to('gemini.generate') ?>" method="post" enctype="multipart/form-data">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="card-title fw-bold mb-0">Gemini AI</h2>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="reportToggle" name="report" value="1">
                                <label class="form-check-label" for="reportToggle">Report</label>
                            </div>
                        </div>
                        <?= csrf_field() ?>

                        <?php if (!empty($prompts)): ?>
                            <div class="row g-2 mb-3 align-items-center">
                                <div class="col">
                                    <div class="form-floating">
                                        <select class="form-select" id="savedPrompts" aria-label="Saved Prompts">
                                            <option selected disabled>Select a saved prompt...</option>
                                            <?php foreach ($prompts as $p): ?>
                                                <option value="<?= esc($p->prompt_text) ?>" data-id="<?= $p->id ?>"><?= esc($p->title) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="savedPrompts">Saved Prompts</label>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button type="button" id="usePromptBtn" class="btn btn-outline-secondary">Use</button>
                                </div>
                                <div class="col-auto">
                                    <button type="button" id="deletePromptBtn" class="btn btn-outline-danger" disabled>Delete</button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-floating mb-3">
                            <textarea id="prompt" name="prompt" class="form-control" placeholder="Enter your prompt" style="height: 150px" required><?= old('prompt') ?></textarea>
                            <label for="prompt">Your Prompt</label>
                        </div>

                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#savePromptModal">
                                <i class="bi bi-plus-circle"></i> Save New Prompt
                            </button>
                        </div>

                        <div id="mediaInputContainer" class="mb-2">
                            <div class="mb-3 media-input-row">
                                <label for="media" class="form-label">Upload Media (Optional)</label>
                                <div class="file-input-group">
                                    <input type="file" class="form-control" name="media[]" multiple>
                                    <button type="button" class="btn btn-outline-danger remove-media-btn">Remove</button>
                                </div>
                            </div>
                        </div>

                        <button type="button" id="addMediaBtn" class="btn btn-secondary btn-sm mb-4"><i class="bi bi-plus-circle"></i> Add Another File</button>

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
                        <h3 class="fw-bold mb-4 d-flex justify-content-between align-items-center">AI Response <button id="copy-response-btn" class="btn btn-sm btn-outline-primary">Copy</button></h3>
                        <pre id="ai-response-content"><?= esc($result) ?></pre>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Save Prompt Modal -->
<div class="modal fade" id="savePromptModal" tabindex="-1" aria-labelledby="savePromptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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
                        mainPromptTextarea.value = selectedOption.value;
                    }
                });
            }

            if (deletePromptBtn) {
                deletePromptBtn.addEventListener('click', function() {
                    if (!selectedPromptId || this.disabled) return;

                    if (confirm('Are you sure you want to delete this prompt?')) {
                        const deleteUrl = `<?= rtrim(url_to('gemini.prompts.delete', 0), '0') ?>${selectedPromptId}`;
                        geminiForm.action = deleteUrl;
                        geminiForm.submit();
                    }
                });
            }
        }

        // --- Media File Input Management ---
        const addMediaBtn = document.getElementById('addMediaBtn');
        const mediaContainer = document.getElementById('mediaInputContainer');
        const maxMediaFiles = 6;

        const updateMediaButtons = () => {
            const mediaRows = mediaContainer.querySelectorAll('.media-input-row');
            // Manage 'Add' button visibility
            addMediaBtn.style.display = mediaRows.length < maxMediaFiles ? 'block' : 'none';
            addMediaBtn.disabled = mediaRows.length >= maxMediaFiles;

            // Manage 'Remove' button visibility
            mediaRows.forEach((row) => {
                const removeBtn = row.querySelector('.remove-media-btn');
                const input = row.querySelector('input[type="file"]');
                if (removeBtn && input) {
                    const hasFile = input.files.length > 0;
                    // Show remove button if there's more than one row OR if this single row has a file
                    removeBtn.style.display = (mediaRows.length > 1 || hasFile) ? 'inline-block' : 'none';
                }
            });
        };

        if (addMediaBtn && mediaContainer) {
            addMediaBtn.addEventListener('click', function() {
                const newRow = document.createElement('div');
                newRow.className = 'mb-3 media-input-row';
                newRow.innerHTML = `
                <div class="file-input-group">
                    <input type="file" class="form-control" name="media[]" multiple>
                    <button type="button" class="btn btn-outline-danger remove-media-btn">Remove</button>
                </div>
            `;
                mediaContainer.appendChild(newRow);
                updateMediaButtons();
            });

            mediaContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-media-btn')) {
                    // When removing, clear the file input value to ensure the change is registered
                    const input = event.target.closest('.media-input-row').querySelector('input[type="file"]');
                    if (input) {
                        input.value = '';
                    }
                    event.target.closest('.media-input-row').remove();
                    updateMediaButtons();
                }
            });

            // Add a change listener to the container to detect when a file is selected
            mediaContainer.addEventListener('change', function(event) {
                if (event.target.matches('input[type="file"]')) {
                    updateMediaButtons();
                }
            });

            // Initial check on page load to set correct button visibility
            updateMediaButtons();
        }

        // --- Form Submission Loading State ---
        if (geminiForm && submitButton) {
            geminiForm.addEventListener('submit', function(event) {
                if (this.action.includes('delete')) {
                    return;
                }

                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';
                submitButton.disabled = true;
            });
        }

        // --- Copy to Clipboard Functionality ---
        const copyBtn = document.getElementById('copy-response-btn');
        const responseContent = document.getElementById('ai-response-content');

        if (copyBtn && responseContent) {
            copyBtn.addEventListener('click', function() {
                navigator.clipboard.writeText(responseContent.innerText).then(() => {
                    const originalText = copyBtn.innerText;
                    copyBtn.innerText = 'Copied!';
                    setTimeout(() => {
                        copyBtn.innerText = originalText;
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            });
        }
    });
</script>
<?= $this->endSection() ?>