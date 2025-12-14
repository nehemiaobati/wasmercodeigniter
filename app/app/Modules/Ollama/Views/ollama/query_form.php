<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/highlight/styles/atom-one-dark.min.css') ?>">
<style>
    :root {
        --code-bg: #282c34;
    }

    /* Desktop-only full height */
    @media (min-width: 992px) {
        .prompt-card {
            min-height: calc(100vh - 210px);
        }
    }

    /* Mobile Toast Centering */
    @media (max-width: 991.98px) {
        .toast-container {
            left: 50% !important;
            transform: translateX(-50%) !important;
            right: auto !important;
        }
    }

    .prompt-editor-wrapper {
        height: 190px;
        overflow-y: auto;
    }

    /* Results Card - Account for Sticky Header */
    #results-card {
        scroll-margin-top: 100px;
    }

    /* Code Block Styling with Copy Button */
    pre {
        background: var(--code-bg);
        color: #fff;
        padding: 1rem;
        border-radius: 5px;
        position: relative;
        margin-top: 1rem;
    }

    .copy-code-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        opacity: 0.4;
        transition: opacity 0.2s;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    pre:hover .copy-code-btn {
        opacity: 1;
    }

    /* Upload Area & File Items */
    #mediaUploadArea {
        border: 2px dashed var(--bs-border-color);
        padding: 1rem;
        background: var(--bs-tertiary-bg);
        transition: 0.2s;
    }

    @media (min-width: 992px) {
        #mediaUploadArea {
            padding: 2rem;
        }
    }

    #mediaUploadArea.dragover {
        background: var(--bs-primary-bg-subtle);
        border-color: var(--bs-primary);
    }

    .file-item {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
    }

    .file-item .progress {
        height: 4px;
        margin-top: 4px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-3 my-lg-5">
    <!-- Header -->
    <div class="blueprint-header text-center mb-4">
        <h1 class="fw-bold"><i class="bi bi-cpu text-success"></i> Local AI Studio (Ollama)</h1>
    </div>

    <div class="row g-4">
        <!-- Left Column: Input -->
        <div class="col-lg-8">
            <form id="ollamaForm" action="<?= url_to('ollama.generate') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="card blueprint-card prompt-card">
                    <div class="card-body p-0 d-flex flex-column">
                        <!-- Editor -->
                        <div class="prompt-editor-wrapper p-3 flex-grow-1">
                            <textarea id="prompt" name="prompt" class="visually-hidden"><?= old('prompt') ?></textarea>
                        </div>

                        <!-- Upload & Actions -->
                        <div class="p-3 border-top bg-body-tertiary">
                            <div id="mediaUploadArea" class="mb-3 text-center rounded">
                                <input type="file" id="media-input-trigger" multiple class="d-none">
                                <label for="media-input-trigger" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-paperclip"></i> Attach Images
                                </label>
                                <!-- File List Container -->
                                <div id="upload-list-wrapper" class="mt-3 text-start"></div>
                                <!-- Hidden Inputs Container -->
                                <div id="uploaded-files-container"></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <button type="button" class="btn btn-link text-decoration-none" data-bs-toggle="modal" data-bs-target="#savePromptModal">
                                    <i class="bi bi-bookmark-plus"></i> Save Prompt
                                </button>
                                <button type="submit" id="generateBtn" class="btn btn-success fw-bold px-4">
                                    <i class="bi bi-lightning-charge"></i> Generate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Model Selection (Hidden Input populated by sidebar) -->
                <input type="hidden" name="model" id="selectedModelInput" value="<?= $availableModels[0] ?? 'llama3' ?>">
            </form>
        </div>

        <!-- Right Column: Settings -->
        <div class="col-lg-4">
            <div class="card blueprint-card">
                <div class="card-header bg-transparent fw-bold"><i class="bi bi-sliders"></i> Configuration</div>
                <div class="card-body">

                    <!-- Model Selector -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Model</label>
                        <select class="form-select" id="modelSelector">
                            <?php foreach ($availableModels as $model): ?>
                                <option value="<?= esc($model) ?>"><?= esc($model) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-muted small mt-1">
                            Select the Ollama model to use.
                        </div>
                    </div>

                    <!-- Toggles -->
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input setting-toggle" type="checkbox" id="assistantMode"
                            data-key="assistant_mode_enabled" <?= $assistant_mode_enabled ? 'checked' : '' ?>>
                        <label class="form-check-label" for="assistantMode">Assistant Mode</label>
                        <div class="form-text text-muted small mt-1">
                            Maintains context from previous messages.
                        </div>
                    </div>

                    <!-- Saved Prompts -->
                    <label class="form-label small fw-bold text-uppercase text-muted">Saved Prompts</label>
                    <?php if (!empty($prompts)): ?>
                        <div class="input-group mb-3">
                            <select class="form-select" id="savedPrompts">
                                <option value="" disabled selected>Select...</option>
                                <?php foreach ($prompts as $p): ?>
                                    <option value="<?= esc($p->prompt_text, 'attr') ?>" data-id="<?= $p->id ?>"><?= esc($p->title) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-secondary" type="button" id="usePromptBtn">Load</button>
                            <button class="btn btn-outline-danger" type="button" id="deletePromptBtn" disabled title="Delete Saved Prompt">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border mb-3 small text-muted">
                            <i class="bi bi-info-circle me-1"></i> No saved prompts yet.
                        </div>
                    <?php endif; ?>

                    <!-- Clear Memory -->
                    <hr>
                    <form action="<?= url_to('ollama.memory.clear') ?>" method="post" onsubmit="return confirm('Are you sure?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                            <i class="bi bi-trash"></i> Clear History
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <?php if ($result = session()->getFlashdata('result')): ?>
        <div class="card blueprint-card mt-5 shadow-lg border-success" id="results-card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">Ollama Output</span>
                <div>
                    <button class="btn btn-sm btn-light" id="copyFullResponseBtn" title="Copy Full Text">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                    <!-- Export Dropdown -->
                    <div class="btn-group ms-2">
                        <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item download-action" href="#" data-format="pdf"><i class="bi bi-file-pdf text-danger"></i> PDF Document</a></li>
                            <li><a class="dropdown-item download-action" href="#" data-format="docx"><i class="bi bi-file-word text-primary"></i> Word Document</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body response-content" id="ai-response-body">
                <?= $result ?>
            </div>
            <textarea id="raw-response" class="d-none"><?= esc(session()->getFlashdata('raw_result')) ?></textarea>
        </div>
    <?php endif; ?>
</div>

<!-- Hidden Delete Prompt Form -->
<form id="deletePromptForm" method="post" action="" class="d-none">
    <?= csrf_field() ?>
</form>

<!-- Hidden Download Form -->
<form id="downloadForm" action="<?= url_to('ollama.download_document') ?>" method="post" target="_blank" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="content" id="downloadContent">
    <input type="hidden" name="format" id="downloadFormat">
</form>

<!-- Save Prompt Modal -->
<div class="modal fade" id="savePromptModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url_to('ollama.prompts.add') ?>" method="post" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Save Prompt</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                <div class="mb-3"><label>Content</label><textarea name="prompt_text" id="modalPromptText" class="form-control" rows="4" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
        </form>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast text-bg-dark" role="alert">
        <div class="toast-body"></div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/highlight/highlight.js') ?>"></script>
<script src="<?= base_url('assets/tinymce/tinymce.min.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- 1. Config & State ---
        const appState = {
            csrfName: '<?= csrf_token() ?>',
            csrfHash: document.querySelector('input[name="<?= csrf_token() ?>"]').value,
            maxFileSize: <?= $maxFileSize ?>,
            maxFiles: <?= $maxFiles ?>,
            endpoints: {
                upload: '<?= url_to('ollama.upload_media') ?>',
                deleteMedia: '<?= url_to('ollama.delete_media') ?>',
                settings: '<?= url_to('ollama.settings.update') ?>',
                deletePromptBase: '<?= url_to('ollama.prompts.delete', 0) ?>'.slice(0, -1)
            }
        };

        // Queue State
        const uploadQueue = [];
        let isUploading = false;

        // --- 2. Utils ---
        const refreshCsrf = (hash) => {
            if (!hash) return;
            appState.csrfHash = hash;
            document.querySelectorAll(`input[name="${appState.csrfName}"]`).forEach(el => el.value = hash);
        };

        const showToast = (msg) => {
            const t = document.getElementById('liveToast');
            t.querySelector('.toast-body').textContent = msg;
            new bootstrap.Toast(t).show();
        };

        const updateUIState = (uiElement, type, message = '') => {
            const pBar = uiElement.querySelector('.progress-bar');
            const statusTxt = uiElement.querySelector('.status-text');
            const rmBtn = uiElement.querySelector('.remove-btn');

            pBar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            rmBtn.classList.remove('disabled');

            if (type === 'success') {
                pBar.classList.add('bg-success');
                statusTxt.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
            } else {
                pBar.classList.add('bg-danger');
                pBar.style.width = '100%';
                statusTxt.innerHTML = `<span class="text-danger small" title="${message}">${message}</span>`;
            }
        };

        // --- 3. TinyMCE ---
        tinymce.init({
            selector: '#prompt',
            height: '100%',
            menubar: false,
            statusbar: false,
            plugins: 'autolink lists',
            toolbar: 'blocks | bold italic strikethrough | bullist numlist | link | alignleft aligncenter alignright | clean',
            placeholder: 'Ask Ollama something...',
            license_key: 'gpl',
            mobile: {
                menubar: false,
                toolbar: 'bold italic | bullist numlist | link',
                height: 300
            },
            setup: (ed) => {
                ed.on('change', () => ed.save());
            }
        });

        // --- 4. Model Selector Sync ---
        const modelSelector = document.getElementById('modelSelector');
        const hiddenInput = document.getElementById('selectedModelInput');
        if (modelSelector) {
            modelSelector.addEventListener('change', (e) => {
                hiddenInput.value = e.target.value;
            });
        }

        // --- 5. Settings Toggles ---
        document.querySelectorAll('.setting-toggle').forEach(toggle => {
            toggle.addEventListener('change', async (e) => {
                const formData = new FormData();
                formData.append(appState.csrfName, appState.csrfHash);
                formData.append('setting_key', e.target.dataset.key);
                formData.append('enabled', e.target.checked);

                try {
                    const res = await fetch(appState.endpoints.settings, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    refreshCsrf(data.csrf_token);
                    showToast(data.status === 'success' ? 'Setting saved.' : 'Failed to save.');
                } catch (err) {
                    console.error(err);
                    showToast('Network error.');
                }
            });
        });

        // --- 6. File Upload Logic ---
        const uploadArea = document.getElementById('mediaUploadArea');
        const fileInput = document.getElementById('media-input-trigger');
        const listWrapper = document.getElementById('upload-list-wrapper');

        ['dragenter', 'dragover'].forEach(evt => {
            uploadArea.addEventListener(evt, (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
        });
        ['dragleave', 'drop'].forEach(evt => {
            uploadArea.addEventListener(evt, (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            });
        });

        const createProgressBar = (file, id) => {
            const div = document.createElement('div');
            div.id = `file-item-${id}`;
            div.className = 'file-item d-flex align-items-center gap-3 rounded p-2 mb-2';
            div.innerHTML = `
            <div class="flex-grow-1" style="min-width: 0;">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="fw-bold text-truncate file-name"></span>
                    <span class="status-text text-muted">Waiting...</span>
                </div>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger border-0 remove-btn disabled" data-id="${id}">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
            div.querySelector('.file-name').textContent = file.name;
            listWrapper.appendChild(div);
            return div;
        };

        const processQueue = () => {
            if (isUploading || uploadQueue.length === 0) return;
            isUploading = true;
            const job = uploadQueue.shift();
            performUpload(job);
        };

        const performUpload = (job) => {
            const {
                file,
                uiElement,
                uniqueId
            } = job;
            uiElement.querySelector('.status-text').textContent = "Uploading...";

            const xhr = new XMLHttpRequest();
            const formData = new FormData();
            formData.append(appState.csrfName, appState.csrfHash);
            formData.append('file', file);

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    uiElement.querySelector('.progress-bar').style.width = percent + '%';
                    uiElement.querySelector('.status-text').innerText = percent + '%';
                }
            });

            xhr.onreadystatechange = () => {
                if (xhr.readyState === 4) {
                    let response = {};
                    try {
                        response = JSON.parse(xhr.responseText);
                        if (response.csrf_token) refreshCsrf(response.csrf_token);
                    } catch (e) {}

                    if (xhr.status === 200 && response.status === 'success') {
                        updateUIState(uiElement, 'success');
                        const rmBtn = uiElement.querySelector('.remove-btn');
                        rmBtn.dataset.serverFileId = response.file_id;

                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'uploaded_media[]';
                        input.value = response.file_id;
                        input.id = `input-${uniqueId}`;
                        document.getElementById('uploaded-files-container').appendChild(input);
                    } else {
                        updateUIState(uiElement, 'error', response.message || 'Upload failed');
                    }
                    isUploading = false;
                    processQueue();
                }
            };

            xhr.open('POST', appState.endpoints.upload, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        };

        const handleFiles = (files) => {
            const currentUploadedCount = document.querySelectorAll('input[name="uploaded_media[]"]').length;
            if (currentUploadedCount + uploadQueue.length + files.length > appState.maxFiles) {
                showToast(`Max ${appState.maxFiles} files.`);
                return;
            }

            Array.from(files).forEach(file => {
                const uniqueId = Math.random().toString(36).substr(2, 9);
                const uiElement = createProgressBar(file, uniqueId);
                uploadQueue.push({
                    file,
                    uiElement,
                    uniqueId
                });
            });
            fileInput.value = '';
            processQueue();
        };

        fileInput.addEventListener('change', (e) => handleFiles(e.target.files));
        uploadArea.addEventListener('drop', (e) => handleFiles(e.dataTransfer.files));

        // --- 7. Remove File Logic ---
        listWrapper.addEventListener('click', async (e) => {
            const btn = e.target.closest('.remove-btn');
            if (!btn || btn.classList.contains('disabled')) return;

            const uiId = btn.dataset.id;
            const serverId = btn.dataset.serverFileId;
            const uiItem = document.getElementById(`file-item-${uiId}`);
            uiItem.style.opacity = '0.5';

            if (serverId) {
                const formData = new FormData();
                formData.append('file_id', serverId);
                formData.append(appState.csrfName, appState.csrfHash);

                try {
                    const res = await fetch(appState.endpoints.deleteMedia, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.csrf_token) refreshCsrf(data.csrf_token);
                    if (data.status === 'success') {
                        uiItem.remove();
                        document.getElementById(`input-${uiId}`)?.remove();
                    } else {
                        uiItem.style.opacity = '1';
                    }
                } catch (err) {
                    uiItem.style.opacity = '1';
                }
            } else {
                uiItem.remove();
            }
        });

        // --- 8. Copy Full Response ---
        const copyFullBtn = document.getElementById('copyFullResponseBtn');
        if (copyFullBtn) {
            copyFullBtn.addEventListener('click', () => {
                const rawText = document.getElementById('raw-response').value;
                navigator.clipboard.writeText(rawText).then(() => {
                    const original = copyFullBtn.innerHTML;
                    copyFullBtn.innerHTML = '<i class="bi bi-check-lg"></i> Copied';
                    setTimeout(() => copyFullBtn.innerHTML = original, 2000);
                });
            });
        }

        // --- 9. Saved Prompts ---
        const savedSelect = document.getElementById('savedPrompts');
        const deletePromptBtn = document.getElementById('deletePromptBtn');
        if (savedSelect) {
            savedSelect.addEventListener('change', () => {
                deletePromptBtn.disabled = !savedSelect.value;
            });
            document.getElementById('usePromptBtn').addEventListener('click', () => {
                const val = savedSelect.value;
                if (val) tinymce.get('prompt').setContent(val);
            });
            if (deletePromptBtn) {
                deletePromptBtn.addEventListener('click', () => {
                    const selectedOption = savedSelect.options[savedSelect.selectedIndex];
                    const promptId = selectedOption.dataset.id;
                    if (promptId && confirm('Delete this prompt?')) {
                        const form = document.getElementById('deletePromptForm');
                        form.action = appState.endpoints.deletePromptBase + promptId;
                        form.submit();
                    }
                });
            }
        }

        // --- 10. Highlight & Modal ---
        hljs.highlightAll();
        document.getElementById('savePromptModal').addEventListener('show.bs.modal', () => {
            document.getElementById('modalPromptText').value = tinymce.get('prompt').getContent({
                format: 'text'
            });
        });

        document.getElementById('ollamaForm').addEventListener('submit', function() {
            const btn = document.getElementById('generateBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Thinking...';
        });

        // --- 11. Auto Scroll ---
        const resultsCard = document.getElementById('results-card');
        if (resultsCard) {
            setTimeout(() => {
                resultsCard.scrollIntoView({
                    behavior: 'smooth'
                });
            }, 100);
        }

        // --- 12. Export Document ---
        document.querySelectorAll('.download-action').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const format = e.currentTarget.dataset.format;
                const content = document.getElementById('raw-response').value; // Use raw markdown

                document.getElementById('downloadContent').value = content;
                document.getElementById('downloadFormat').value = format;
                document.getElementById('downloadForm').submit();
            });
        });
    });
</script>
<?= $this->endSection() ?>