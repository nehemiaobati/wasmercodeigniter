<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
<style>
    :root {
        --border-color: #dadce0;
        --code-bg: #282c34;
    }

    .prompt-card {
        height: calc(100vh - 260px);
        min-height: 500px;
    }

    .prompt-editor-wrapper {
        overflow-y: auto;
        position: relative;
    }

    .code-block-wrapper {
        position: relative;
        margin: 1rem 0;
    }

    .code-block-wrapper pre {
        background-color: var(--code-bg);
        padding: 1.5rem;
        border-radius: 0.5rem;
        white-space: pre-wrap;
        word-wrap: break-word;
        border: none;
        padding-top: 3rem;
        margin: 0;
    }

    .copy-code-btn {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        z-index: 10;
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 0.25rem;
        cursor: pointer;
        opacity: 0.6;
        transition: opacity 0.2s ease-in-out;
    }

    .code-block-wrapper:hover .copy-code-btn {
        opacity: 1;
    }

    #mediaUploadArea {
        border: 2px dashed var(--border-color);
        border-radius: 0.5rem;
        padding: 1.5rem;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    #mediaUploadArea.dragover {
        background-color: #e8f0fe;
        border-color: var(--bs-primary);
    }

    #file-progress-container .file-name {
        font-size: 0.9rem;
        color: #5f6368;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 150px;
    }

    .tox-tinymce {
        border-radius: 0.5rem !important;
        border: 1px solid var(--border-color) !important;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="blueprint-header text-center mb-4">
        <h1 class="fw-bold d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-stars fs-1 text-primary"></i> AI Studio
        </h1>
        <p class="text-muted lead">Your creative canvas to chat, analyze, or generate anything.</p>
    </div>

    <div id="audio-player-container" class="mb-4"></div>

    <div class="row g-4">
        <div class="col-lg-8">
            <form id="geminiForm" action="<?= url_to('gemini.generate') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="blueprint-card p-0 prompt-card d-flex flex-column">
                    <div class="prompt-editor-wrapper p-4 flex-grow-1">
                        <label for="prompt" class="form-label fw-bold visually-hidden">Your Prompt</label>
                        <textarea id="prompt" name="prompt" style="visibility: hidden;"><?= old('prompt') ?></textarea>
                    </div>
                    <div class="p-4 border-top action-footer ">
                        <div id="mediaUploadArea" class="mb-3 bg-body-tertiary ">
                            <input type="file" id="media-input-trigger" multiple class="d-none">
                            <label for="media-input-trigger" class="btn btn-outline-secondary w-100"><i class="bi bi-paperclip"></i> Attach Files or Drag & Drop</label>
                            <div id="upload-list-wrapper" class="mt-3 text-start" style="max-height: 120px; overflow-y: auto; padding-right: 10px;">
                                <div id="file-progress-container"></div>
                            </div>
                            <div id="uploaded-files-container"></div>
                        </div>
                        <div class="d-flex justify-content-end align-items-center gap-2 ">
                            <button type="button" class="btn btn-link text-decoration-none" data-bs-toggle="modal" data-bs-target="#savePromptModal" title="Save this prompt"><i class="bi bi-bookmark-plus"></i> Save</button>
                            <button type="submit" id="generateBtn" class="btn btn-primary btn-lg fw-bold"><i class="bi bi-sparkles"></i> Generate</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="blueprint-card p-4">
                <h4 class="card-title fw-bold mb-4"><i class="bi bi-gear-fill"></i> Settings</h4>
                <div id="settingsContainer">
                    <div class="pb-3 mb-3 border-bottom">
                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                            <label class="form-check-label h5 mb-0" for="assistantModeToggle">Conversational Memory</label>
                            <input class="form-check-input" type="checkbox" role="switch" id="assistantModeToggle" name="assistant_mode" value="1" <?= $assistant_mode_enabled ? 'checked' : '' ?>>
                        </div>
                        <small class="text-muted d-block mt-1">Remember previous conversations for follow-up questions.</small>
                    </div>

                    <div class="pb-3 mb-3 border-bottom">
                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                            <label class="form-check-label h5 mb-0" for="voiceOutputToggle">Voice Output</label>
                            <input class="form-check-input" type="checkbox" role="switch" id="voiceOutputToggle" name="voice_output" value="1" <?= $voice_output_enabled ? 'checked' : '' ?>>
                        </div>
                        <small class="text-muted d-block mt-1">Automatically play the AI's response as audio.</small>
                    </div>
                </div>
                <?php if (!empty($prompts)): ?>
                    <div class="pb-3 mb-3 border-bottom">
                        <h5 class="fw-bold">Saved Prompts</h5>
                        <div class="input-group"><select class="form-select" id="savedPrompts">
                                <option selected disabled>Select a prompt...</option><?php foreach ($prompts as $p): ?><option value="<?= esc($p->prompt_text) ?>" data-id="<?= $p->id ?>"><?= esc($p->title) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="d-flex gap-2 mt-2"><button type="button" id="usePromptBtn" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-arrow-down-circle"></i> Use</button><button type="button" id="deletePromptBtn" class="btn btn-sm btn-outline-danger w-100" disabled><i class="bi bi-trash"></i> Delete</button></div>
                    </div>
                <?php endif; ?>
                <div>
                    <h5 class="fw-bold">Memory Management</h5>
                    <p class="small text-muted mb-2">Permanently delete all past interactions from conversation history.</p>
                    <form id="clearMemoryForm" action="<?= url_to('gemini.memory.clear') ?>" method="post"><?= csrf_field() ?><div class="d-grid"><button type="submit" id="clearMemoryBtn" class="btn btn-outline-danger w-100"><i class="bi bi-trash"></i> Clear All Memory</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($result = session()->getFlashdata('result')): ?>
        <div class="row g-4 mt-4">
            <div class="col-12">
                <div class="blueprint-card p-4 p-md-5" id="results-card">
                    <h3 class="fw-bold mb-4 d-flex justify-content-between align-items-center flex-wrap">
                        <span>Studio Output</span>
                        <div class="btn-group mt-2 mt-sm-0" role="group">
                            <button id="copy-response-btn" class="btn btn-sm btn-outline-secondary" title="Copy Full Response"><i class="bi bi-clipboard"></i></button>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                                    <i class="bi bi-download"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" id="download-pdf-btn">Download as PDF</a></li>
                                    <li><a class="dropdown-item" href="#" id="download-word-btn">Download as Word (DOCX)</a></li>
                                </ul>
                            </div>
                        </div>
                    </h3>
                    <div id="ai-response-wrapper"></div>
                    <textarea id="raw-response-for-copy" class="visually-hidden"><?= esc(session()->getFlashdata('raw_result') ?? strip_tags($result)) ?></textarea>
                    <div id="final-rendered-content" class="visually-hidden"><?= $result ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="savePromptModal" tabindex="-1" aria-labelledby="savePromptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content blueprint-card">
            <form id="savePromptForm" action="<?= url_to('gemini.prompts.add') ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="savePromptModalLabel">Save New Prompt</h5> <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"> <?= csrf_field() ?> <div class="form-floating mb-3"> <input type="text" class="form-control" id="promptTitle" name="title" placeholder="Prompt Title" required> <label for="promptTitle">Prompt Title</label> </div>
                    <div class="form-floating"> <textarea class="form-control" placeholder="Prompt Text" id="modalPromptText" name="prompt_text" style="height: 100px" required></textarea> <label for="modalPromptText">Prompt Text</label> </div>
                </div>
                <div class="modal-footer"> <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> <button type="submit" class="btn btn-primary">Save Prompt</button> </div>
            </form>
        </div>
    </div>
</div>
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
    <div id="settingsToast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
        <div class="d-flex">
            <div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<!-- FIX: Re-added the download form that was missing -->
<form id="documentDownloadForm" action="<?= url_to('gemini.download_document') ?>" method="post" target="_blank" class="d-none">
    <?= csrf_field() ?>
    <textarea name="raw_response" id="download-raw-response"></textarea>
    <input type="hidden" name="format" id="download-format">
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="<?= base_url('assets/tinymce/tinymce.min.js') ?>" referrerpolicy="origin"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        <?php if ($audio_url = session()->getFlashdata('audio_url')): ?>
            // --- FIX: Use the secure audio serving route ---
            const audioUrl = '<?= url_to("gemini.serve_audio", basename(esc($audio_url, 'js'))) ?>';
            const audioPlayerContainer = document.getElementById('audio-player-container');
            if (audioUrl && audioPlayerContainer) {
                const audioPlayer = document.createElement('audio');
                audioPlayer.controls = true;
                audioPlayer.autoplay = true;
                audioPlayer.src = audioUrl;
                audioPlayer.classList.add('w-100');
                audioPlayerContainer.innerHTML = ''; 
                audioPlayerContainer.appendChild(audioPlayer);
            }
        <?php endif; ?>

        const app = {
            csrfToken: document.querySelector('input[name="<?= csrf_token() ?>"]').value,
            elements: {
                geminiForm: document.getElementById('geminiForm'),
                clearMemoryForm: document.getElementById('clearMemoryForm'),
                generateBtn: document.getElementById('generateBtn'),
                clearMemoryBtn: document.getElementById('clearMemoryBtn'),
                assistantModeToggle: document.getElementById('assistantModeToggle'),
                voiceOutputToggle: document.getElementById('voiceOutputToggle'),
                settingsToast: new bootstrap.Toast(document.getElementById('settingsToast')),
                mediaInput: document.getElementById('media-input-trigger'),
                mediaUploadArea: document.getElementById('mediaUploadArea'),
                progressContainer: document.getElementById('file-progress-container'),
                uploadedFilesContainer: document.getElementById('uploaded-files-container'),
                savedPromptsSelect: document.getElementById('savedPrompts'),
                usePromptBtn: document.getElementById('usePromptBtn'),
                deletePromptBtn: document.getElementById('deletePromptBtn'),
                savePromptModal: document.getElementById('savePromptModal'),
                modalPromptTextarea: document.getElementById('modalPromptText'),
                responseWrapper: document.getElementById('ai-response-wrapper'),
                copyBtn: document.getElementById('copy-response-btn'),
                downloadPdfBtn: document.getElementById('download-pdf-btn'),
                downloadWordBtn: document.getElementById('download-word-btn'),
            },
            urls: {
                upload: "<?= route_to('gemini.upload_media') ?>",
                deleteMedia: "<?= route_to('gemini.delete_media') ?>",
                updateSettings: "<?= route_to('gemini.settings.updateAssistantMode') ?>",
                updateVoiceSettings: "<?= route_to('gemini.settings.updateVoiceOutputMode') ?>",
                deletePromptBase: "<?= rtrim(route_to('gemini.prompts.delete', 0), '0') ?>",
            },

            init() {
                this.initTinyMCE();
                this.bindEvents();
                if (this.elements.responseWrapper) {
                    this.handleResponseOutput();
                }
            },

            initTinyMCE() {
                tinymce.init({
                    selector: '#prompt',
                    plugins: 'autolink link lists ',
                    height: '100%',
                    menubar: false,
                    statusbar: false,
                    toolbar: 'blocks | bold italic strikethrough | bullist numlist | link | alignleft aligncenter alignright',
                    block_formats: 'Text=p; Heading 1=h1; Heading 2=h2; Heading 3=h3',
                    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 16px }',
                    placeholder: 'Enter your prompt here...',
                    license_key: 'gpl',
                    init_instance_callback: (editor) => {
                        editor.getContainer().style.visibility = 'visible';
                    }
                });
            },

            bindEvents() {
                this.elements.geminiForm?.addEventListener('submit', this.handleFormSubmit.bind(this));
                this.elements.clearMemoryForm?.addEventListener('submit', this.handleClearMemorySubmit.bind(this));
                this.elements.assistantModeToggle?.addEventListener('change', this.handleAssistantSettingsChange.bind(this));
                this.elements.voiceOutputToggle?.addEventListener('change', this.handleVoiceSettingsChange.bind(this));
                const dndEvents = ['dragenter', 'dragover', 'dragleave', 'drop'];
                dndEvents.forEach(eName => this.elements.mediaUploadArea?.addEventListener(eName, e => { e.preventDefault(); e.stopPropagation(); }));
                ['dragenter', 'dragover'].forEach(eName => this.elements.mediaUploadArea?.addEventListener(eName, () => this.elements.mediaUploadArea.classList.add('dragover')));
                ['dragleave', 'drop'].forEach(eName => this.elements.mediaUploadArea?.addEventListener(eName, () => this.elements.mediaUploadArea.classList.remove('dragover')));
                this.elements.mediaInput?.addEventListener('change', (e) => this.handleFiles(e.target.files));
                this.elements.mediaUploadArea?.addEventListener('drop', e => this.handleFiles(e.dataTransfer.files));
                this.elements.progressContainer?.addEventListener('click', this.handleFileDelete.bind(this));
                this.elements.savedPromptsSelect?.addEventListener('change', e => this.elements.deletePromptBtn.disabled = !e.target.options[e.target.selectedIndex].dataset.id);
                this.elements.usePromptBtn?.addEventListener('click', this.handleUsePrompt.bind(this));
                this.elements.deletePromptBtn?.addEventListener('click', this.handleDeletePrompt.bind(this));
                this.elements.savePromptModal?.addEventListener('show.bs.modal', this.handleModalShow.bind(this));
                this.elements.copyBtn?.addEventListener('click', this.handleCopyResponse.bind(this));
                this.elements.downloadPdfBtn?.addEventListener('click', (e) => { e.preventDefault(); this.handleDownload('pdf'); });
                this.elements.downloadWordBtn?.addEventListener('click', (e) => { e.preventDefault(); this.handleDownload('docx'); });
                window.addEventListener('pageshow', this.restoreButtonStates.bind(this));
            },

            handleFormSubmit(e) {
                tinymce.triggerSave();
                if (!tinymce.get('prompt').getContent({ format: 'text' }).trim() && this.elements.uploadedFilesContainer.children.length === 0) {
                    e.preventDefault();
                    alert('Please enter a prompt or upload a file before generating.');
                    return;
                }
                this.setLoadingState(this.elements.generateBtn, 'Generating...');
            },

            handleClearMemorySubmit(e) {
                if (!confirm('Are you sure you want to permanently delete your entire conversation history? This action cannot be undone.')) {
                    e.preventDefault();
                }
                this.setLoadingState(this.elements.clearMemoryBtn, 'Clearing...');
            },

            async handleAssistantSettingsChange(e) {
                await this.updateSetting(this.urls.updateSettings, e.target.checked, 'Conversational Memory');
            },
            
            async handleVoiceSettingsChange(e) {
                await this.updateSetting(this.urls.updateVoiceSettings, e.target.checked, 'Voice Output');
            },

            async updateSetting(url, isEnabled, featureName) {
                const formData = new FormData();
                formData.append('enabled', isEnabled);
                try {
                    const data = await this.fetchWithCsrf(url, { method: 'POST', body: formData });
                    this.showToast(data.status === 'success' ? `${featureName} ${isEnabled ? 'enabled' : 'disabled'}.` : 'Error saving setting.');
                } catch (error) {
                    this.showToast(`Network error. Could not save ${featureName} setting.`);
                    console.error(`Error updating ${featureName} setting:`, error);
                }
            },

            handleFiles(files) {
                [...files].forEach(file => this.uploadFile(file));
                this.elements.mediaInput.value = '';
            },

            async handleFileDelete(e) {
                const removeBtn = e.target.closest('.remove-file-btn');
                if (!removeBtn) return;
                const fileToDelete = removeBtn.dataset.fileId;
                const uiElementId = removeBtn.dataset.uiId;
                const formData = new FormData();
                formData.append('file_id', fileToDelete);
                try {
                    const data = await this.fetchWithCsrf(this.urls.deleteMedia, { method: 'POST', body: formData });
                    if (data.status === 'success') {
                        document.getElementById(uiElementId)?.remove();
                        document.getElementById(`hidden-${uiElementId}`)?.remove();
                    } else {
                        alert('Could not remove file: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error deleting file:', error);
                }
            },
            
            handleUsePrompt() {
                const selectedOption = this.elements.savedPromptsSelect.options[this.elements.savedPromptsSelect.selectedIndex];
                const selectedPromptText = selectedOption?.value;
                if (selectedPromptText) {
                    tinymce.get('prompt').setContent(selectedPromptText);
                }
            },

            handleDeletePrompt() {
                const selectedOption = this.elements.savedPromptsSelect.options[this.elements.savedPromptsSelect.selectedIndex];
                const selectedPromptId = selectedOption?.dataset.id;
                if (!selectedPromptId || this.elements.deletePromptBtn.disabled) return;
                if (confirm('Are you sure you want to delete this prompt?')) {
                    const tempForm = document.createElement('form');
                    tempForm.method = 'post';
                    tempForm.action = `${this.urls.deletePromptBase}${selectedPromptId}`;
                    tempForm.innerHTML = `<input type="hidden" name="<?= csrf_token() ?>" value="${this.csrfToken}">`;
                    document.body.appendChild(tempForm);
                    tempForm.submit();
                }
            },

            handleModalShow() {
                this.elements.modalPromptTextarea.value = tinymce.get('prompt').getContent({ format: 'html' });
            },

            handleResponseOutput() {
                this.elements.responseWrapper.innerHTML = document.getElementById('final-rendered-content').innerHTML;
                this.setupResponseFormatting();
                setTimeout(() => {
                    document.getElementById('results-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            },
            
            handleCopyResponse() {
                const rawText = document.getElementById('raw-response-for-copy').value;
                navigator.clipboard.writeText(rawText).then(() => {
                    this.setButtonFeedback(this.elements.copyBtn, '<i class="bi bi-check-lg"></i>', '<i class="bi bi-clipboard"></i>');
                });
            },

            handleDownload(format) {
                document.getElementById('download-raw-response').value = document.getElementById('raw-response-for-copy').value;
                document.getElementById('download-format').value = format;
                document.getElementById('documentDownloadForm').submit();
            },

            uploadFile(file) {
                const fileId = `file-${Math.random().toString(36).substr(2, 9)}`;
                const progressItem = document.createElement('div');
                progressItem.className = 'progress-item d-flex align-items-center gap-3 mb-3';
                progressItem.id = fileId;
                progressItem.innerHTML = `<span class="file-name" title="${file.name}">${file.name}</span><div class="progress flex-grow-1" style="height: 8px;"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div></div><span class="status-icon text-muted fs-5"><i class="bi bi-hourglass-split"></i></span>`;
                this.elements.progressContainer.appendChild(progressItem);

                const xhr = new XMLHttpRequest();
                const formData = new FormData();
                formData.append('file', file);
                formData.append('<?= csrf_token() ?>', this.csrfToken);

                xhr.open('POST', this.urls.upload, true);

                xhr.upload.onprogress = e => {
                    if (e.lengthComputable) progressItem.querySelector('.progress-bar').style.width = `${(e.loaded / e.total) * 100}%`;
                };

                xhr.onload = () => {
                    const progressBar = progressItem.querySelector('.progress-bar');
                    const statusIcon = progressItem.querySelector('.status-icon');
                    progressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');
                    try {
                        const response = JSON.parse(xhr.responseText);
                        this.updateCsrfToken(response.csrf_token);
                        if (xhr.status === 200) {
                            progressBar.classList.add('bg-success');
                            statusIcon.innerHTML = `<button type="button" class="btn btn-sm btn-link text-danger p-0 remove-file-btn" data-file-id="${response.file_id}" data-ui-id="${fileId}"><i class="bi bi-x-circle-fill"></i></button>`;
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'uploaded_media[]';
                            hiddenInput.value = response.file_id;
                            hiddenInput.id = `hidden-${fileId}`;
                            this.elements.uploadedFilesContainer.appendChild(hiddenInput);
                        } else {
                            progressBar.classList.add('bg-danger');
                            statusIcon.innerHTML = `<i class="bi bi-x-circle-fill text-danger" title="${response.message || 'Upload failed'}"></i>`;
                        }
                    } catch (e) {
                        progressBar.classList.add('bg-danger');
                        statusIcon.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger" title="An unknown error occurred."></i>`;
                    }
                };

                xhr.onerror = () => {
                    const progressBar = progressItem.querySelector('.progress-bar');
                    progressBar.classList.remove('progress-bar-striped', 'progress-bar-animated', 'bg-success').add('bg-danger');
                    progressItem.querySelector('.status-icon').innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger" title="Network error."></i>`;
                };

                xhr.send(formData);
            },

            setupResponseFormatting() {
                this.elements.responseWrapper.querySelectorAll('pre').forEach(pre => {
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
                            this.setButtonFeedback(copyButton, 'Copied!', '<i class="bi bi-clipboard"></i> Copy');
                        });
                    });
                    wrapper.appendChild(copyButton);
                });
                if (typeof hljs !== 'undefined') {
                    this.elements.responseWrapper.querySelectorAll('pre code').forEach((block) => {
                        hljs.highlightElement(block);
                    });
                }
            },

            async fetchWithCsrf(url, options = {}) {
                options.body.append('<?= csrf_token() ?>', this.csrfToken);
                const response = await fetch(url, { ...options, headers: { 'X-Requested-With': 'XMLHttpRequest', ...options.headers } });
                const data = await response.json();
                if (data.csrf_token) this.updateCsrfToken(data.csrf_token);
                if (!response.ok) throw new Error(data.message || 'Request failed');
                return data;
            },

            updateCsrfToken(newToken) {
                if (!newToken) return;
                this.csrfToken = newToken;
                document.querySelectorAll('input[name="<?= csrf_token() ?>"]').forEach(input => input.value = newToken);
            },

            setLoadingState(button, text) {
                button.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${text}`;
                button.disabled = true;
            },

            setButtonFeedback(button, feedbackText, originalText) {
                const originalWidth = button.offsetWidth;
                button.style.width = `${originalWidth}px`;
                button.innerHTML = feedbackText;
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 2000);
            },

            showToast(message) {
                this.elements.settingsToast._element.querySelector('.toast-body').textContent = message;
                this.elements.settingsToast.show();
            },

            restoreButtonStates() {
                if (this.elements.generateBtn) {
                    this.elements.generateBtn.innerHTML = `<i class="bi bi-sparkles"></i> Generate`;
                    this.elements.generateBtn.disabled = false;
                }
                if (this.elements.clearMemoryBtn) {
                    this.elements.clearMemoryBtn.innerHTML = `<i class="bi bi-trash"></i> Clear All Memory`;
                    this.elements.clearMemoryBtn.disabled = false;
                }
            }
        };

        app.init();
    });
</script>
<?= $this->endSection() ?>