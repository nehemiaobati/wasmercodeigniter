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
        padding-top: 3rem; /* Make space for copy button */
    }
    
    .code-block-wrapper {
        position: relative;
    }

    .copy-code-btn {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        z-index: 10;
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        color: #fff;
        background-color: #6c757d;
        border: none;
        border-radius: 0.25rem;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s ease-in-out;
    }

    .code-block-wrapper:hover .copy-code-btn {
        opacity: 1;
    }

    /* Settings Card specific styles */
    .settings-card .card-body {
        display: flex;
        flex-direction: column;
    }
    .settings-card .form-check-label {
        font-weight: 500;
    }
    .settings-card .saved-prompts-block, .settings-card .memory-management {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e9ecef;
    }
    
    /* MODIFIED: Toast notification for settings save - Removed custom fadeOut animation */
    .toast.show {
        animation: slideIn 0.3s ease-out;
    }
    @keyframes slideIn { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

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
    
    #file-progress-container .progress-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.75rem;
        animation: fadeIn 0.3s ease-in-out;
    }
    #file-progress-container .progress {
        height: 10px;
        flex-grow: 1;
    }
    #file-progress-container .file-name {
        font-size: 0.9rem;
        color: #6c757d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 150px;
    }
     #file-progress-container .status-icon {
        font-size: 1.2rem;
     }

    /* Typing cursor animation */
    #ai-response-wrapper.typing::after {
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
    
    /* TinyMCE editor container style */
    .tox-tinymce {
        border-radius: 0.5rem !important;
        border: 1px solid #ced4da !important;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="blueprint-header text-center mb-5">
        <h1 class="fw-bold"><i class="bi bi-stars text-primary"></i> AI Studio</h1>
        <p class="text-muted lead">This is your creative canvas. Chat, analyze, or generate anything you can imagine.</p>
    </div>

    
    <div class="row g-4">
        <!-- Left Column: Settings & Config -->
        <div class="col-lg-4">
            <div class="card settings-card blueprint-card">
                <div class="card-body p-4">
                    <div id="settingsContainer">
                        <h4 class="card-title fw-bold mb-4">
                            <i class="bi bi-gear-fill"></i> Settings
                        </h4>
                        <div class="form-check form-switch fs-5 p-0 d-flex justify-content-between align-items-center">
                            <label class="form-check-label" for="assistantModeToggle">Conversational Memory</label>
                            <input class="form-check-input" type="checkbox" role="switch" id="assistantModeToggle" name="assistant_mode" value="1" <?= $assistant_mode_enabled ? 'checked' : '' ?>>
                        </div>
                        <small class="text-muted d-block mt-1">Turn on to let the AI remember your previous conversations. Great for follow-up questions and multi-step tasks.</small>
                    </div>

                    <!-- SEPARATE FORM FOR CLEARING MEMORY -->
                    <div class="memory-management">
                        <label class="form-label fw-bold">Memory Management</label>
                        <p class="small text-muted mb-2">Permanently delete all past interactions and learned concepts from your AI's memory.</p>
                        <form id="clearMemoryForm" action="<?= url_to('gemini.memory.clear') ?>" method="post">
                            <?= csrf_field() ?>
                            <div class="d-grid">
                                <button type="submit" id="clearMemoryBtn" class="btn btn-outline-danger">
                                    <i class="bi bi-trash"></i> Clear All Memory
                                </button>
                            </div>
                        </form>
                    </div>
                    
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
            <form id="geminiForm" action="<?= url_to('gemini.generate') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="card query-card blueprint-card">
                    <div class="card-body p-4 p-md-5">
                        
                        <div class="mb-3">
                            <label for="prompt" class="form-label fw-bold">Your Prompt</label>
                            <!-- MODIFIED: Removed 'required' attribute -->
                            <textarea id="prompt" name="prompt"><?= old('prompt') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-end mb-4">
                            <button type="button" class="btn btn-link text-decoration-none btn-sm" data-bs-toggle="modal" data-bs-target="#savePromptModal">
                                <i class="bi bi-bookmark-plus"></i> Save this prompt
                            </button>
                        </div>

                        <div id="mediaUploadArea" class="mb-4">
                            <input type="file" id="media-input-trigger" multiple class="d-none">
                            <label for="media-input-trigger" class="btn btn-secondary w-100"><i class="bi bi-paperclip"></i> Attach Files or Drag & Drop</label>
                            <div id="upload-list-wrapper" style="max-height: 200px; overflow-y: auto; padding-right: 10px;">
                                <div id="file-progress-container" class="mt-3"></div>
                            </div>
                            <div id="uploaded-files-container"></div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" id="generateBtn" class="btn btn-primary btn-lg fw-bold"><i class="bi bi-sparkles"></i> Generate</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    

    <?php 
        $result = session()->getFlashdata('result');
        $raw_result = session()->getFlashdata('raw_result');
        if ($result):
    ?>
        <div class="row justify-content-center mt-4">
            <div class="col-lg-12">
                <div class="card results-card blueprint-card">
                    <div class="card-body p-4 p-md-5">
                        <h3 class="fw-bold mb-4 d-flex justify-content-between align-items-center">
                            Studio Output
                            <button id="copy-response-btn" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-clipboard"></i> Copy Full Response
                            </button>
                        </h3>
                        <div id="ai-response-wrapper" class="ai-response-html">
                             <!-- This will be populated by the typing effect -->
                        </div>
                        <textarea id="raw-response-for-copy" class="visually-hidden"><?= esc($raw_result ?? strip_tags($result)) ?></textarea>
                        <div id="final-rendered-content" class="visually-hidden"><?= $result ?></div>
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

<!-- Toast container for notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
  <!-- MODIFIED: Added data-bs-delay attribute for standard Bootstrap auto-hide functionality -->
  <div id="settingsToast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
    <div class="d-flex">
      <div class="toast-body">
        <!-- Message will be set by JS -->
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

<!-- MODIFIED: Load self-hosted TinyMCE instead of cloud version -->
<script src="<?= base_url('assets/tinymce/tinymce.min.js') ?>" referrerpolicy="origin"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // MODIFIED: Added block_formats to customize the dropdown
        tinymce.init({
            selector: '#prompt',
            height: 250,
            menubar: false,
            statusbar: false,
            plugins: 'autolink link lists',
            toolbar: 'blocks | bold italic strikethrough | bullist numlist | link | alignleft aligncenter alignright',
            block_formats: 'Text=p; Heading 1=h1; Heading 2=h2; Heading 3=h3', // THIS LINE IS THE FIX
            content_style: 'body { font-family:Poppins,sans-serif; font-size:16px }',
            license_key: 'gpl'
        });

        const geminiForm = document.getElementById('geminiForm');
        const clearMemoryForm = document.getElementById('clearMemoryForm');
        const mainPromptTextarea = document.getElementById('prompt');
        let csrfToken = document.querySelector('input[name="<?= csrf_token() ?>"]').value;
        const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        
        const uploadUrl = "<?= route_to('gemini.upload_media') ?>";
        const deleteUrl = "<?= route_to('gemini.delete_media') ?>";
        const updateSettingsUrl = "<?= route_to('gemini.settings.updateAssistantMode') ?>";

        // --- Settings Save Logic ---
        const assistantModeToggle = document.getElementById('assistantModeToggle');
        const settingsToastEl = document.getElementById('settingsToast');
        const settingsToast = new bootstrap.Toast(settingsToastEl);

        if (assistantModeToggle) {
            assistantModeToggle.addEventListener('change', function() {
                const isEnabled = this.checked;
                const formData = new FormData();
                formData.append('enabled', isEnabled);
                formData.append('<?= csrf_token() ?>', csrfToken);

                fetch(updateSettingsUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Update CSRF token on the page for all forms
                    csrfToken = data.csrf_token;
                    document.querySelectorAll('input[name="<?= csrf_token() ?>"]').forEach(input => {
                        input.value = data.csrf_token;
                    });

                    // Show feedback toast
                    const toastBody = settingsToastEl.querySelector('.toast-body');
                    if (data.status === 'success') {
                        toastBody.textContent = `Conversational Memory ${isEnabled ? 'enabled' : 'disabled'}.`;
                    } else {
                        toastBody.textContent = 'Error saving setting.';
                    }
                    settingsToast.show();
                })
                .catch(error => {
                    console.error('Error updating setting:', error);
                    const toastBody = settingsToastEl.querySelector('.toast-body');
                    toastBody.textContent = 'Network error. Could not save setting.';
                    settingsToast.show();
                });
            });
        }
        
        // --- Form Submission Loading States ---
        const generateBtn = document.getElementById('generateBtn');
        const clearMemoryBtn = document.getElementById('clearMemoryBtn');

        if (geminiForm && generateBtn) {
            geminiForm.addEventListener('submit', function(e) {
                // First, ensure the latest editor content is saved to the underlying textarea
                tinymce.triggerSave();

                // Manually check if the editor's content is empty
                const promptContent = tinymce.get('prompt').getContent({ format: 'text' });
                
                if (!promptContent || promptContent.trim() === '') {
                    // Prevent the form from submitting
                    e.preventDefault(); 
                    alert('Please enter a prompt before generating.');
                    return;
                }

                // If content exists, proceed with showing the loading state
                generateBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...`;
                generateBtn.disabled = true;
            });
        }

        if (clearMemoryForm && clearMemoryBtn) {
            clearMemoryForm.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to permanently delete your entire conversation history? This action cannot be undone.')) {
                    e.preventDefault(); // Stop form submission if user clicks cancel
                    return;
                }
                clearMemoryBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Clearing...`;
                clearMemoryBtn.disabled = true;
            });
        }

        // Restore button state on page load (for back button usage)
        window.addEventListener('pageshow', function() {
            if (generateBtn) {
                generateBtn.innerHTML = `<i class="bi bi-sparkles"></i> Generate`;
                generateBtn.disabled = false;
            }
            if (clearMemoryBtn) {
                clearMemoryBtn.innerHTML = `<i class="bi bi-trash"></i> Clear All Memory`;
                clearMemoryBtn.disabled = false;
            }
        });

        // --- AJAX File Upload Logic ---
        const mediaInput = document.getElementById('media-input-trigger');
        const mediaUploadArea = document.getElementById('mediaUploadArea');
        const progressContainer = document.getElementById('file-progress-container');
        const uploadedFilesContainer = document.getElementById('uploaded-files-container');
        
        const handleFiles = (files) => {
            [...files].forEach(uploadFile);
        };

        mediaInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
            e.target.value = '';
        });
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            mediaUploadArea.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); });
        });
        ['dragenter', 'dragover'].forEach(eventName => mediaUploadArea.addEventListener(eventName, () => mediaUploadArea.classList.add('dragover')));
        ['dragleave', 'drop'].forEach(eventName => mediaUploadArea.addEventListener(eventName, () => mediaUploadArea.classList.remove('dragover')));
        
        mediaUploadArea.addEventListener('drop', e => handleFiles(e.dataTransfer.files));
        
        const uploadFile = (file) => {
            const fileId = `file-${Math.random().toString(36).substr(2, 9)}`;
            const progressItem = document.createElement('div');
            progressItem.className = 'progress-item';
            progressItem.id = fileId;
            progressItem.innerHTML = `
                <span class="file-name" title="${file.name}">${file.name}</span>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <span class="status-icon text-muted"><i class="bi bi-hourglass-split"></i></span>
            `;
            progressContainer.appendChild(progressItem);

            const xhr = new XMLHttpRequest();
            const formData = new FormData();
            formData.append('file', file);
            formData.append('<?= csrf_token() ?>', csrfToken);
            
            xhr.open('POST', uploadUrl, true);

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressItem.querySelector('.progress-bar').style.width = `${percentComplete}%`;
                }
            };

            xhr.onload = () => {
                const progressBar = progressItem.querySelector('.progress-bar');
                const statusIcon = progressItem.querySelector('.status-icon');
                progressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');

                try {
                    const response = JSON.parse(xhr.responseText);
                    csrfToken = response.csrf_token;
                    // Update all csrf tokens on the page
                    document.querySelectorAll('input[name="<?= csrf_token() ?>"]').forEach(input => {
                        input.value = response.csrf_token;
                    });


                    if (xhr.status === 200) {
                        progressBar.classList.add('bg-success');
                        statusIcon.innerHTML = `<button type="button" class="btn btn-sm btn-link text-danger p-0 remove-file-btn" data-file-id="${response.file_id}" data-ui-id="${fileId}"><i class="bi bi-x-circle-fill"></i></button>`;

                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'uploaded_media[]';
                        hiddenInput.value = response.file_id;
                        hiddenInput.id = `hidden-${fileId}`;
                        uploadedFilesContainer.appendChild(hiddenInput);
                    } else {
                        progressBar.classList.add('bg-danger');
                        statusIcon.innerHTML = `<i class="bi bi-x-circle-fill text-danger" title="${response.message || 'Upload failed'}"></i>`;
                        console.error('Upload failed:', response.message);
                    }
                } catch (e) {
                     progressBar.classList.add('bg-danger');
                     statusIcon.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger" title="An unknown error occurred."></i>`;
                     console.error('An unknown error occurred:', xhr.responseText);
                }
            };

            xhr.onerror = () => {
                const progressBar = progressItem.querySelector('.progress-bar');
                progressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');
                progressBar.classList.add('bg-danger');
                progressItem.querySelector('.status-icon').innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger" title="Network error."></i>`;
                console.error('Network error during upload.');
            };

            xhr.send(formData);
        };
        
        progressContainer.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-file-btn');
            if (removeBtn) {
                const fileToDelete = removeBtn.dataset.fileId;
                const uiElementId = removeBtn.dataset.uiId;
                
                const formData = new FormData();
                formData.append('file_id', fileToDelete);
                formData.append('<?= csrf_token() ?>', csrfToken);

                fetch(deleteUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    csrfToken = data.csrf_token;
                    document.querySelectorAll('input[name="<?= csrf_token() ?>"]').forEach(input => {
                        input.value = data.csrf_token;
                    });

                    if (data.status === 'success') {
                        document.getElementById(uiElementId)?.remove();
                        document.getElementById(`hidden-${uiElementId}`)?.remove();
                    } else {
                        alert('Could not remove file: ' + data.message);
                    }
                })
                .catch(error => console.error('Error deleting file:', error));
            }
        });

        // --- Saved Prompts Logic ---
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
                        // MODIFIED: Set content in the TinyMCE editor
                        tinymce.get('prompt').setContent(selectedOption.value);
                    }
                });
            }

            if (deletePromptBtn) {
                deletePromptBtn.addEventListener('click', function() {
                    if (!selectedPromptId || this.disabled) return;
                    if (confirm('Are you sure you want to delete this prompt?')) {
                        const deleteUrl = `<?= rtrim(route_to('gemini.prompts.delete', 0), '0') ?>${selectedPromptId}`;
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
        
        // --- Modal Logic ---
        const savePromptModalEl = document.getElementById('savePromptModal');
        const modalPromptTextarea = document.getElementById('modalPromptText');

        if (savePromptModalEl) {
            savePromptModalEl.addEventListener('show.bs.modal', () => {
                // Get the current content from the editor when the modal opens
                modalPromptTextarea.value = tinymce.get('prompt').getContent({ format: 'html' });
            });
        }
        
        // --- AI Response and Copy Logic ---
        const responseWrapper = document.getElementById('ai-response-wrapper');
        const copyBtn = document.getElementById('copy-response-btn');

        function setupResponseFormatting() {
            const allPreTags = responseWrapper.querySelectorAll('pre');
            allPreTags.forEach(pre => {
                if (pre.parentElement.classList.contains('code-block-wrapper')) return;

                const wrapper = document.createElement('div');
                wrapper.className = 'code-block-wrapper';
                pre.parentNode.insertBefore(wrapper, pre);
                wrapper.appendChild(pre);

                const copyButton = document.createElement('button');
                copyButton.className = 'copy-code-btn';
                copyButton.innerHTML = '<i class="bi bi-clipboard"></i> Copy';
                copyButton.addEventListener('click', () => {
                    const codeToCopy = pre.querySelector('code')?.innerText || pre.innerText;
                    navigator.clipboard.writeText(codeToCopy).then(() => {
                        copyButton.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
                        setTimeout(() => { copyButton.innerHTML = '<i class="bi bi-clipboard"></i> Copy'; }, 2000);
                    });
                });
                wrapper.appendChild(copyButton);
            });
            if (typeof hljs !== 'undefined') {
                responseWrapper.querySelectorAll('pre code').forEach((block) => {
                    hljs.highlightElement(block);
                });
            }
        }

        if (responseWrapper && copyBtn) {
            const rawTextarea = document.getElementById('raw-response-for-copy');
            const finalRenderedContent = document.getElementById('final-rendered-content');
            const resultsCard = responseWrapper.closest('.results-card');

            setTimeout(() => {
                resultsCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);

            copyBtn.addEventListener('click', function() {
                navigator.clipboard.writeText(rawTextarea.value).then(() => {
                    this.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
                    setTimeout(() => { this.innerHTML = '<i class="bi bi-clipboard"></i> Copy Full Response'; }, 2000);
                });
            });
            
            // The typing effect is removed to instantly render the complex HTML from the rich text output
            responseWrapper.innerHTML = finalRenderedContent.innerHTML;
            setupResponseFormatting();

        }
    });
</script>
<?= $this->endSection() ?>