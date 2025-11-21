<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/highlight/styles/atom-one-dark.min.css') ?>">
<style>
    :root {
        --code-bg: #282c34;
    }

    .prompt-card {
        min-height: calc(100vh - 210px);
    }

    .prompt-editor-wrapper {
        height: 190px;
        overflow-y: auto;
    }

    /* Results Card - Account for Sticky Header */
    #results-card {
        scroll-margin-top: 100px; /* Adjust based on your header height */
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
        padding: 2rem;
        background: var(--bs-tertiary-bg);
        transition: 0.2s;
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
<div class="container my-5">
    <!-- Header -->
    <div class="blueprint-header text-center mb-4">
        <h1 class="fw-bold"><i class="bi bi-stars text-primary"></i> AI Studio</h1>
    </div>

    <!-- Audio Player -->
    <?php if (session()->getFlashdata('audio_url')): ?>
        <div class="alert alert-info d-flex align-items-center">
            <i class="bi bi-volume-up-fill fs-4 me-3"></i>
            <audio controls autoplay class="w-100">
                <source src="<?= url_to('gemini.serve_audio', session()->getFlashdata('audio_url')) ?>" type="audio/mpeg">
            </audio>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left Column: Input -->
        <div class="col-lg-8">
            <form id="geminiForm" action="<?= url_to('gemini.generate') ?>" method="post" enctype="multipart/form-data">
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
                                    <i class="bi bi-paperclip"></i> Attach Files
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
                                <button type="submit" id="generateBtn" class="btn btn-primary fw-bold px-4">
                                    <i class="bi bi-sparkles"></i> Generate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Column: Settings -->
        <div class="col-lg-4">
            <div class="card blueprint-card">
                <div class="card-header bg-transparent fw-bold"><i class="bi bi-sliders"></i> Configuration</div>
                <div class="card-body">
                    <!-- Toggles -->
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input setting-toggle" type="checkbox" id="assistantMode"
                            data-key="assistant_mode_enabled" <?= $assistant_mode_enabled ? 'checked' : '' ?>>
                        <label class="form-check-label" for="assistantMode">Conversational Memory</label>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input setting-toggle" type="checkbox" id="voiceOutput"
                            data-key="voice_output_enabled" <?= $voice_output_enabled ? 'checked' : '' ?>>
                        <label class="form-check-label" for="voiceOutput">Voice Output (TTS)</label>
                    </div>

                    <!-- Saved Prompts -->
                    <?php if (!empty($prompts)): ?>
                        <label class="form-label small fw-bold text-uppercase text-muted">Saved Prompts</label>
                        <div class="input-group mb-3">
                            <select class="form-select" id="savedPrompts">
                                <option value="" disabled selected>Select...</option>
                                <?php foreach ($prompts as $p): ?>
                                    <option value="<?= esc($p->prompt_text, 'attr') ?>" data-id="<?= $p->id ?>"><?= esc($p->title) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-secondary" type="button" id="usePromptBtn">Load</button>
                        </div>
                    <?php endif; ?>

                    <!-- Clear Memory -->
                    <hr>
                    <form action="<?= url_to('gemini.memory.clear') ?>" method="post" onsubmit="return confirm('Are you sure? This cannot be undone.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                            <i class="bi bi-trash"></i> Clear Conversation History
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <?php if ($result = session()->getFlashdata('result')): ?>
        <div class="card blueprint-card mt-5 shadow-lg border-primary" id="results-card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">Studio Output</span>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-light" id="copyFullResponseBtn" title="Copy Full Text">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">Export</button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item download-action" href="#" data-format="pdf">PDF</a></li>
                            <li><a class="dropdown-item download-action" href="#" data-format="docx">Word</a></li>
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

<!-- Hidden Forms/Modals -->
<form id="downloadForm" method="post" action="<?= url_to('gemini.download_document') ?>" target="_blank" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="raw_response" id="dl_raw">
    <input type="hidden" name="format" id="dl_format">
</form>

<!-- Save Prompt Modal -->
<div class="modal fade" id="savePromptModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url_to('gemini.prompts.add') ?>" method="post" class="modal-content">
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
            maxFileSize: 10 * 1024 * 1024, // 10MB
            endpoints: {
                upload: '<?= url_to('gemini.upload_media') ?>',
                deleteMedia: '<?= url_to('gemini.delete_media') ?>',
                settings: '<?= url_to('gemini.settings.update') ?>'
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
            rmBtn.classList.remove('disabled'); // Always enable remove button on completion

            if (type === 'success') {
                pBar.classList.add('bg-success');
                statusTxt.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
            } else {
                pBar.classList.add('bg-danger');
                pBar.style.width = '100%';
                // Show a short error in UI, full error in tooltip/toast
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
            block_formats: 'Text=p; Heading 1=h1; Heading 2=h2; Heading 3=h3',
            placeholder: 'Enter your prompt here...',
            license_key: 'gpl',
            setup: (ed) => {
                ed.on('change', () => ed.save());
            }
        });

        // --- 4. Settings Toggles ---
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

        // --- 5. File Upload Logic ---
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
                    <span class="fw-bold text-truncate">${file.name}</span>
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
            listWrapper.appendChild(div);
            return div;
        };

        const processQueue = () => {
            // If uploading or empty, stop
            if (isUploading || uploadQueue.length === 0) return;

            isUploading = true;
            const job = uploadQueue.shift(); // Get first item
            performUpload(job);
        };

        const performUpload = (job) => {
            const { file, uiElement, uniqueId } = job;
            
            // Update UI to "Uploading"
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
                        // Always try to refresh CSRF if provided, even on error
                        if (response.csrf_token) refreshCsrf(response.csrf_token);
                    } catch (e) {
                        console.error('Invalid JSON response');
                    }

                    if (xhr.status === 200 && response.status === 'success') {
                        updateUIState(uiElement, 'success');

                        // Attach Server File ID
                        const rmBtn = uiElement.querySelector('.remove-btn');
                        rmBtn.dataset.serverFileId = response.file_id;

                        // Add Hidden Input
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'uploaded_media[]';
                        input.value = response.file_id;
                        input.id = `input-${uniqueId}`;
                        document.getElementById('uploaded-files-container').appendChild(input);

                    } else {
                        const errorMsg = response.message || 'Upload failed';
                        updateUIState(uiElement, 'error', errorMsg);
                    }

                    // Trigger Next
                    isUploading = false;
                    processQueue();
                }
            };

            xhr.open('POST', appState.endpoints.upload, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        };

        const handleFiles = (files) => {
            Array.from(files).forEach(file => {
                const uniqueId = Math.random().toString(36).substr(2, 9);
                const uiElement = createProgressBar(file, uniqueId);

                // 1. Client-Side Validation
                const supportedTypes = [
                    'image/png', 'image/jpeg', 'image/webp', 'audio/mpeg', 'audio/mp3',
                    'audio/wav', 'video/mov', 'video/mpeg', 'video/mp4', 'video/mpg',
                    'video/avi', 'video/wmv', 'video/mpegps', 'video/flv',
                    'application/pdf', 'text/plain'
                ];

                if (file.size > appState.maxFileSize) {
                    updateUIState(uiElement, 'error', 'File too large (Max 10MB)');
                    return; // Don't queue
                }

                if (!supportedTypes.includes(file.type)) {
                    updateUIState(uiElement, 'error', 'Unsupported file type');
                    return; // Don't queue
                }

                // 2. Add to Queue
                uploadQueue.push({ file, uiElement, uniqueId });
            });

            // Reset input
            fileInput.value = '';
            
            // Start processing if idle
            processQueue();
        };

        fileInput.addEventListener('change', (e) => handleFiles(e.target.files));
        uploadArea.addEventListener('drop', (e) => handleFiles(e.dataTransfer.files));

        // --- 6. Remove File Logic ---
        listWrapper.addEventListener('click', async (e) => {
            const btn = e.target.closest('.remove-btn');
            if (!btn || btn.classList.contains('disabled')) return;

            const uiId = btn.dataset.id;
            const serverId = btn.dataset.serverFileId;
            const uiItem = document.getElementById(`file-item-${uiId}`);

            // Optimistically remove from UI
            uiItem.style.opacity = '0.5';

            if (serverId) {
                // If it was uploaded successfully, delete from server
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
                        alert('Failed to delete file from server.');
                        uiItem.style.opacity = '1';
                    }
                } catch (err) {
                    console.error(err);
                    alert('Network error while deleting file.');
                    uiItem.style.opacity = '1';
                }
            } else {
                // If it failed upload or client validation, just remove UI
                uiItem.remove();
            }
        });

        // --- 7. Downloads & Copy ---
        document.querySelectorAll('.download-action').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                document.getElementById('dl_raw').value = document.getElementById('raw-response').value;
                document.getElementById('dl_format').value = e.target.dataset.format;
                document.getElementById('downloadForm').submit();
            });
        });

        // Copy Full Response
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

        // --- 8. Saved Prompts ---
        const savedSelect = document.getElementById('savedPrompts');
        if (savedSelect) {
            document.getElementById('usePromptBtn').addEventListener('click', () => {
                const val = savedSelect.value;
                if (val) tinymce.get('prompt').setContent(val);
            });
        }

        // --- 9. Code Highlighting & Copy Snippets ---
        hljs.highlightAll();

        // Inject Copy Buttons into Code Blocks
        document.querySelectorAll('pre code').forEach((block) => {
            const pre = block.parentElement;

            // Create Button
            const btn = document.createElement('button');
            btn.className = 'btn btn-sm btn-dark copy-code-btn';
            btn.innerHTML = '<i class="bi bi-clipboard"></i>';
            btn.title = 'Copy code';

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                navigator.clipboard.writeText(block.innerText).then(() => {
                    btn.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
                    setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i>', 2000);
                });
            });

            pre.appendChild(btn);
        });

        // --- 10. Modal & Loading ---
        document.getElementById('savePromptModal').addEventListener('show.bs.modal', () => {
            document.getElementById('modalPromptText').value = tinymce.get('prompt').getContent({
                format: 'text'
            });
        });

        document.getElementById('geminiForm').addEventListener('submit', function() {
            const btn = document.getElementById('generateBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Thinking...';
        });

        // --- 11. Auto Scroll to Results (Sticky Header Fix) ---
        const resultsCard = document.getElementById('results-card');
        if (resultsCard) {
            setTimeout(() => {
                resultsCard.scrollIntoView({
                    behavior: 'smooth'
                });
            }, 100);
        }
    });
</script>
<?= $this->endSection() ?>