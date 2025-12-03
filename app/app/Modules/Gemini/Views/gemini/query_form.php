<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('public/assets/highlight/styles/atom-one-dark.min.css') ?>">
<style>
    /* Scoped Styles for Gemini View */
    .gemini-view-container {
        --code-bg: #282c34;
    }

    /* Desktop-only full height */
    @media (min-width: 992px) {
        .gemini-view-container .prompt-card {
            min-height: calc(100vh - 210px);
        }
    }

    .gemini-view-container .prompt-editor-wrapper {
        height: 190px;
        overflow-y: auto;
    }

    /* Results Card - Account for Sticky Header */
    .gemini-view-container #results-card {
        scroll-margin-top: 100px;
    }

    /* Code Block Styling with Copy Button */
    .gemini-view-container pre {
        background: var(--code-bg);
        color: #fff;
        padding: 1rem;
        border-radius: 5px;
        position: relative;
        margin-top: 1rem;
    }

    .gemini-view-container .copy-code-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        opacity: 0.4;
        transition: opacity 0.2s;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .gemini-view-container pre:hover .copy-code-btn {
        opacity: 1;
    }

    /* Upload Area & File Items */
    .gemini-view-container #mediaUploadArea {
        border: 2px dashed var(--bs-border-color);
        padding: 1rem;
        background: var(--bs-tertiary-bg);
        transition: 0.2s;
    }

    @media (min-width: 992px) {
        .gemini-view-container #mediaUploadArea {
            padding: 2rem;
        }
    }

    .gemini-view-container #mediaUploadArea.dragover {
        background: var(--bs-primary-bg-subtle);
        border-color: var(--bs-primary);
    }

    .gemini-view-container .file-item {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
    }

    .gemini-view-container .file-item .progress {
        height: 4px;
        margin-top: 4px;
    }

    /* Model Selection Cards */
    .gemini-view-container .model-card {
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }

    .gemini-view-container .model-card:hover {
        transform: translateY(-4px);
        background-color: var(--bs-gray-100);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .gemini-view-container .model-card.active {
        border-color: var(--bs-primary);
        background-color: var(--bs-primary-bg-subtle);
    }

    .gemini-view-container .model-icon {
        font-size: 1.5rem;
    }

    /* Responsive Toast Positioning */
    .gemini-view-container .gemini-toast-container {
        right: 0;
        left: auto;
        transform: none;
    }

    @media (max-width: 991.98px) {
        .gemini-view-container .gemini-toast-container {
            left: 50%;
            right: auto;
            transform: translateX(-50%);
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-3 my-lg-5 gemini-view-container">
    <!-- Header -->
    <div class="blueprint-header text-center mb-4">
        <h1 class="fw-bold"><i class="bi bi-stars text-primary"></i> AI Studio</h1>
    </div>

    <!-- Audio Player -->
    <?php
    $audioFilePath = session()->getFlashdata('audio_file_path');
    if ($audioFilePath && file_exists($audioFilePath)):
        $audioBase64 = base64_encode(file_get_contents($audioFilePath));
        $mimeType = (pathinfo($audioFilePath, PATHINFO_EXTENSION) === 'mp3') ? 'audio/mp3' : 'audio/wav';
    ?>
        <div class="alert alert-info d-flex align-items-center">
            <i class="bi bi-volume-up-fill fs-4 me-3"></i>
            <audio controls autoplay class="w-100">
                <source src="data:<?= $mimeType ?>;base64,<?= $audioBase64 ?>">
            </audio>
        </div>
    <?php elseif (session()->getFlashdata('audio_url')): ?>
        <div class="alert alert-info d-flex align-items-center">
            <i class="bi bi-volume-up-fill fs-4 me-3"></i>
            <audio controls autoplay class="w-100">
                <source src="<?= url_to('gemini.serve_audio', session()->getFlashdata('audio_url')) ?>">
            </audio>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left Column: Input -->
        <div class="col-lg-8">
            <form id="geminiForm" action="<?= url_to('gemini.generate') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="card blueprint-card prompt-card">
                    <!-- Tabs (Correctly placed in card-header) -->
                    <div class="card-header bg-transparent border-bottom-0 pt-3 px-3 ">
                        <ul class="nav nav-tabs card-header-tabs" id="generationTabs" role="tablist">
                            <!-- Text Tab -->
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="text-tab" data-bs-toggle="tab" data-bs-target="#text-pane" type="button" role="tab" data-type="text" data-model="gemini-2.0-flash">
                                    <i class="bi bi-chat-text me-2"></i>Text
                                </button>
                            </li>
                            <!-- Image Tab -->
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="image-tab" data-bs-toggle="tab" data-bs-target="#image-pane" type="button" role="tab" data-type="image">
                                    <i class="bi bi-image me-2"></i>Image
                                </button>
                            </li>
                            <!-- Video Tab -->
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="video-tab" data-bs-toggle="tab" data-bs-target="#video-pane" type="button" role="tab" data-type="video">
                                    <i class="bi bi-camera-video me-2"></i>Video
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-0 d-flex flex-column">

                        <!-- Model Selection Area -->
                        <div id="model-selection-area" class="p-3 bg-body-tertiary border-bottom d-none">
                            <div class="small fw-bold text-muted mb-2 text-uppercase">Select Model</div>

                            <!-- Image Models Grid -->
                            <div id="image-models-grid" class="row g-2 d-none">
                                <?php if (!empty($mediaConfigs)): ?>
                                    <?php foreach ($mediaConfigs as $modelId => $config): ?>
                                        <?php if (strpos($config['type'], 'image') !== false): ?>
                                            <div class="col-6 col-md-4">
                                                <div class="card model-card h-100" data-model="<?= esc($modelId) ?>" data-type="image">
                                                    <div class="card-body p-2 text-center">
                                                        <div class="model-icon text-primary mb-1"><i class="bi bi-image"></i></div>
                                                        <div class="small fw-bold text-truncate"><?= esc($config['name']) ?></div>
                                                        <div class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill mt-1">$<?= esc($config['cost']) ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Video Models Grid -->
                            <div id="video-models-grid" class="row g-2 d-none">
                                <?php if (!empty($mediaConfigs)): ?>
                                    <?php foreach ($mediaConfigs as $modelId => $config): ?>
                                        <?php if ($config['type'] === 'video'): ?>
                                            <div class="col-6 col-md-4">
                                                <div class="card model-card h-100" data-model="<?= esc($modelId) ?>" data-type="video">
                                                    <div class="card-body p-2 text-center">
                                                        <div class="model-icon text-danger mb-1"><i class="bi bi-camera-video"></i></div>
                                                        <div class="small fw-bold text-truncate"><?= esc($config['name']) ?></div>
                                                        <div class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill mt-1">$<?= esc($config['cost']) ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Editor -->
                        <div class="prompt-editor-wrapper p-3 flex-grow-1">
                            <input type="hidden" name="model_id" id="selectedModelId" value="gemini-2.0-flash">
                            <input type="hidden" name="generation_type" id="generationType" value="text">
                            <textarea id="prompt" name="prompt"><?= old('prompt') ?></textarea>
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
                        <div class="form-text text-muted small mt-1">
                            Maintains context from previous messages for a continuous conversation.
                        </div>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input setting-toggle" type="checkbox" id="voiceOutput"
                            data-key="voice_output_enabled" <?= $voice_output_enabled ? 'checked' : '' ?>>
                        <label class="form-check-label" for="voiceOutput">Voice Output (TTS)</label>
                        <div class="form-text text-muted small mt-1">
                            Reads the AI response aloud using text-to-speech.
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
                            <i class="bi bi-info-circle me-1"></i> No saved prompts yet. Save one after generating!
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
            <div class="card-footer bg-transparent border-0 text-center">
                <small class="text-muted fst-italic"><i class="bi bi-info-circle me-1"></i> AI can make mistakes. Please verify important information.</small>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Hidden Forms/Modals -->
<form id="downloadForm" method="post" action="<?= url_to('gemini.download_document') ?>" target="_blank" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="raw_response" id="dl_raw">
    <input type="hidden" name="format" id="dl_format">
</form>

<!-- Hidden Delete Prompt Form -->
<form id="deletePromptForm" method="post" action="" class="d-none">
    <?= csrf_field() ?>
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

<div class="toast-container position-fixed bottom-0 p-3 gemini-toast-container">
    <div id="liveToast" class="toast text-bg-dark" role="alert">
        <div class="toast-body"></div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('public/assets/highlight/highlight.js') ?>"></script>
<script src="<?= base_url('public/assets/tinymce/tinymce.min.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- 1. Config & State ---
        const appState = {
            csrfName: '<?= csrf_token() ?>',
            csrfHash: document.querySelector('input[name="<?= csrf_token() ?>"]').value,
            maxFileSize: <?= $maxFileSize ?>,
            maxFiles: <?= $maxFiles ?>,
            endpoints: {
                upload: '<?= url_to('gemini.upload_media') ?>',
                deleteMedia: '<?= url_to('gemini.delete_media') ?>',
                settings: '<?= url_to('gemini.settings.update') ?>',
                deletePromptBase: '<?= url_to('gemini.prompts.delete', 0) ?>'.slice(0, -1) // Remove the '0'
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
            mobile: {
                menubar: false,
                toolbar: 'bold italic | bullist numlist | link',
                height: 300
            },
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
            const {
                file,
                uiElement,
                uniqueId
            } = job;

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
            const currentUploadedCount = document.querySelectorAll('input[name="uploaded_media[]"]').length;
            const currentQueueCount = uploadQueue.length;

            if (currentUploadedCount + currentQueueCount + files.length > appState.maxFiles) {
                showToast(`You can only upload a maximum of ${appState.maxFiles} files.`);
                return;
            }

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
                    const maxMB = Math.floor(appState.maxFileSize / (1024 * 1024));
                    updateUIState(uiElement, 'error', `File too large (Max ${maxMB}MB)`);
                    return; // Don't queue
                }

                if (!supportedTypes.includes(file.type)) {
                    updateUIState(uiElement, 'error', 'Unsupported file type');
                    return; // Don't queue
                }

                // 2. Add to Queue
                uploadQueue.push({
                    file,
                    uiElement,
                    uniqueId
                });
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

        // --- 8. Saved Prompts Logic (Load & Delete) ---
        const savedSelect = document.getElementById('savedPrompts');
        const deletePromptBtn = document.getElementById('deletePromptBtn');

        if (savedSelect) {
            // Enable/Disable delete button based on selection
            savedSelect.addEventListener('change', () => {
                const hasValue = !!savedSelect.value;
                if (deletePromptBtn) deletePromptBtn.disabled = !hasValue;
            });

            // Load Prompt
            document.getElementById('usePromptBtn').addEventListener('click', () => {
                const val = savedSelect.value;
                if (val) tinymce.get('prompt').setContent(val);
            });

            // Delete Prompt
            if (deletePromptBtn) {
                deletePromptBtn.addEventListener('click', () => {
                    const selectedOption = savedSelect.options[savedSelect.selectedIndex];
                    const promptId = selectedOption.dataset.id;

                    if (promptId && confirm('Are you sure you want to delete this saved prompt?')) {
                        const form = document.getElementById('deletePromptForm');
                        form.action = appState.endpoints.deletePromptBase + promptId;
                        form.submit();
                    }
                });
            }
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

        // --- 12. Generative Media Logic (Tabs & Polling) ---
        const tabButtons = document.querySelectorAll('#generationTabs button[data-bs-toggle="tab"]');
        const modelInput = document.getElementById('selectedModelId');
        const typeInput = document.getElementById('generationType');
        const form = document.getElementById('geminiForm');
        const generateBtn = document.getElementById('generateBtn');

        const modelSelectionArea = document.getElementById('model-selection-area');
        const imageModelsGrid = document.getElementById('image-models-grid');
        const videoModelsGrid = document.getElementById('video-models-grid');
        const modelCards = document.querySelectorAll('.model-card');

        // Helper to select a model card
        const selectModelCard = (modelId) => {
            modelCards.forEach(card => {
                if (card.dataset.model === modelId) {
                    card.classList.add('active');
                    modelInput.value = modelId;
                } else {
                    card.classList.remove('active');
                }
            });
        };

        // Tab Switching
        tabButtons.forEach(btn => {
            btn.addEventListener('shown.bs.tab', (e) => {
                const type = e.target.dataset.type;
                typeInput.value = type;

                // Reset UI
                modelSelectionArea.classList.add('d-none');
                imageModelsGrid.classList.add('d-none');
                videoModelsGrid.classList.add('d-none');

                const editor = tinymce.get('prompt');

                if (type === 'text') {
                    modelInput.value = 'gemini-2.0-flash'; // Default text model
                    editor.getBody().setAttribute('data-placeholder', 'Enter your prompt here...');
                } else {
                    // Show Selection Area
                    modelSelectionArea.classList.remove('d-none');

                    if (type === 'image') {
                        imageModelsGrid.classList.remove('d-none');
                        editor.getBody().setAttribute('data-placeholder', 'Describe the image you want to generate...');

                        // Auto-select first image model if none active
                        const firstImage = imageModelsGrid.querySelector('.model-card');
                        if (firstImage) selectModelCard(firstImage.dataset.model);

                    } else if (type === 'video') {
                        videoModelsGrid.classList.remove('d-none');
                        editor.getBody().setAttribute('data-placeholder', 'Describe the video you want to create...');

                        // Auto-select first video model
                        const firstVideo = videoModelsGrid.querySelector('.model-card');
                        if (firstVideo) selectModelCard(firstVideo.dataset.model);
                    }
                }
            });
        });

        // Model Card Click Event
        modelCards.forEach(card => {
            card.addEventListener('click', () => {
                selectModelCard(card.dataset.model);
            });
        });

        // Form Submission Intercept
        form.addEventListener('submit', async (e) => {
            const type = typeInput.value;

            // Allow normal submission for Text (handled by existing logic or backend)
            // BUT if we want to handle Media via AJAX, we must intercept.
            if (type === 'text') return;

            e.preventDefault();

            const promptVal = tinymce.get('prompt').getContent({
                format: 'text'
            }).trim();
            if (!promptVal) {
                showToast('Please enter a prompt.');
                return;
            }

            // UI Loading State
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';

            const formData = new FormData();
            formData.append(appState.csrfName, appState.csrfHash);
            formData.append('prompt', promptVal);
            formData.append('model_id', modelInput.value);

            // --- MOCK TESTING INTERCEPT (Start) ---
            // To remove mock functionality, delete this block.
            if (handleMockGeneration(promptVal, type)) return;
            // --- MOCK TESTING INTERCEPT (End) ---

            try {
                const res = await fetch('<?= url_to('gemini.media.generate') ?>', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await res.json();

                // CRITICAL: Always refresh CSRF token if returned, regardless of success/error
                if (data.token) {
                    refreshCsrf(data.token);
                }

                if (data.status === 'error') {
                    showToast(data.message || 'Generation failed.');
                    resetBtn();
                    return;
                }

                if (data.type === 'image') {
                    // Show Image Result
                    showMediaResult(data.url, 'image');
                    resetBtn();
                } else if (data.type === 'video') {
                    // Start Polling
                    pollVideo(data.op_id);
                }

            } catch (err) {
                console.error(err);
                showToast('Network error.');
                resetBtn();
            }
        });

        // --- MOCK GENERATION LOGIC (Start) ---
        // Returns true if a mock request was handled, false otherwise.
        const handleMockGeneration = (prompt, type) => {
            const lowerPrompt = prompt.toLowerCase();

            if (lowerPrompt === 'test image' && type === 'image') {
                setTimeout(() => {
                    showMediaResult('https://picsum.photos/200/300?random=' + new Date().getTime(), 'image');
                    resetBtn();
                    showToast('Mock image generated successfully.');
                }, 1500);
                return true;
            }

            if (lowerPrompt === 'test video' && type === 'video') {
                setTimeout(() => {
                    // Using a sample video for testing
                    showMediaResult('http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', 'video');
                    resetBtn();
                    showToast('Mock video generated successfully.');
                }, 2000);
                return true;
            }

            return false;
        };
        // --- MOCK GENERATION LOGIC (End) ---

        const resetBtn = () => {
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="bi bi-sparkles"></i> Generate';
        };

        const showMediaResult = (url, type) => {
            // Prepare Download Function Call
            const downloadFn = `downloadMedia('${url}', '${type}')`;

            // Create or Update Result Card
            let resultContainer = document.getElementById('media-result-container');
            if (!resultContainer) {
                resultContainer = document.createElement('div');
                resultContainer.id = 'media-result-container';
                resultContainer.className = 'card blueprint-card mt-5 shadow-lg border-primary';
                // Append to the main container (parent of the row) to span full width
                document.querySelector('.container.my-3.my-lg-5').appendChild(resultContainer);
            }

            // Update Header with Download Button
            resultContainer.innerHTML = `
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Studio Output</span>
                    <div>
                        <button onclick="${downloadFn}" class="btn btn-sm btn-light" id="mediaDownloadBtn">
                            <i class="bi bi-download me-1"></i> Download
                        </button>
                    </div>
                </div>
                <div class="card-body text-center p-4" id="media-content-area"></div>
            `;

            const contentArea = document.getElementById('media-content-area');
            let mediaHtml = '';

            if (type === 'image') {
                mediaHtml = `<img src="${url}" class="img-fluid rounded shadow-sm" alt="Generated Image">`;
            } else if (type === 'video') {
                mediaHtml = `
                    <video controls autoplay loop class="w-100 rounded shadow-sm">
                        <source src="${url}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>`;
            }

            contentArea.innerHTML = mediaHtml;

            resultContainer.scrollIntoView({
                behavior: 'smooth'
            });
        };

        // Global function for downloading media
        window.downloadMedia = async (url, type) => {
            // 1. Internal URL (Real API): Use backend forced download
            if (url.includes('gemini/media/serve')) {
                const downloadUrl = url + (url.includes('?') ? '&' : '?') + 'download=1';
                window.location.href = downloadUrl;
                return;
            }

            // --- MOCK DOWNLOAD LOGIC (Start) ---
            // To remove mock functionality, delete this block.
            await handleMockDownload(url, type);
            // --- MOCK DOWNLOAD LOGIC (End) ---
        };

        // --- MOCK DOWNLOAD HANDLER (Start) ---
        const handleMockDownload = async (url, type) => {
            try {
                const btn = document.getElementById('mediaDownloadBtn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Downloading...';
                btn.disabled = true;

                const response = await fetch(url);
                const blob = await response.blob();
                const blobUrl = window.URL.createObjectURL(blob);

                const a = document.createElement('a');
                a.href = blobUrl;
                // Generate filename: mock_image_123.jpg
                const ext = type === 'video' ? 'mp4' : 'jpg';
                a.download = `mock_${type}_${new Date().getTime()}.${ext}`;
                document.body.appendChild(a);
                a.click();

                window.URL.revokeObjectURL(blobUrl);
                document.body.removeChild(a);

                btn.innerHTML = originalText;
                btn.disabled = false;
            } catch (err) {
                console.error('Download failed:', err);
                alert('Failed to download media.');
            }
        };
        // --- MOCK DOWNLOAD HANDLER (End) ---

        const pollVideo = async (opId) => {
            generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing Video...';

            const pollInterval = setInterval(async () => {
                try {
                    const formData = new FormData();
                    formData.append(appState.csrfName, appState.csrfHash);
                    formData.append('op_id', opId);

                    const res = await fetch('<?= url_to('gemini.media.poll') ?>', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await res.json();
                    if (data.token) refreshCsrf(data.token);

                    if (data.status === 'completed') {
                        clearInterval(pollInterval);
                        showMediaResult(data.url, 'video');
                        resetBtn();
                    } else if (data.status === 'failed') {
                        clearInterval(pollInterval);
                        showToast(data.message || 'Video generation failed.');
                        resetBtn();
                    }
                    // If pending, continue polling

                } catch (err) {
                    console.error(err);
                    // Don't stop polling immediately on network glitch, but maybe count errors?
                    // For now, let's just log.
                }
            }, 5000); // Poll every 5 seconds
        };
    });
</script>
<?= $this->endSection() ?>