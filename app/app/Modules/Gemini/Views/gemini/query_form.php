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
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input setting-toggle" type="checkbox" id="streamOutput"
                            data-key="stream_output_enabled" <?= $stream_output_enabled ? 'checked' : '' ?>>
                        <label class="form-check-label" for="streamOutput">Stream Responses</label>
                        <div class="form-text text-muted small mt-1">
                            Typewriter effect (faster perception).
                        </div>
                    </div>

                    <!-- Saved Prompts -->
                    <label class="form-label small fw-bold text-uppercase text-muted">Saved Prompts</label>
                    <div id="saved-prompts-wrapper">
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
                    </div>

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
<script src="<?= base_url('public/assets/marked/marked.min.js') ?>"></script>
<script>
    /**
     * Gemini Module - Frontend Application
     * Refactored into modular classes for improved maintainability and scalability.
     */

    class GeminiApp {
        constructor() {
            this.config = {
                csrfName: '<?= csrf_token() ?>',
                csrfHash: document.querySelector('input[name="<?= csrf_token() ?>"]').value,
                maxFileSize: <?= $maxFileSize ?>,
                maxFiles: <?= $maxFiles ?>,
                endpoints: {
                    upload: '<?= url_to('gemini.upload_media') ?>',
                    deleteMedia: '<?= url_to('gemini.delete_media') ?>',
                    settings: '<?= url_to('gemini.settings.update') ?>',
                    deletePromptBase: '<?= url_to('gemini.prompts.delete', 0) ?>'.slice(0, -1),
                    stream: '<?= url_to('gemini.stream') ?>',
                    generate: '<?= url_to('gemini.generate') ?>',
                    generateMedia: '<?= url_to('gemini.media.generate') ?>',
                    pollMedia: '<?= url_to('gemini.media.poll') ?>'
                }
            };

            // Modules
            this.ui = new UIManager(this);
            this.uploader = new MediaUploader(this);
            this.prompts = new PromptManager(this);
            this.interaction = new InteractionHandler(this);
        }

        init() {
            // Configure Marked.js to handle line breaks like PHP Parsedown
            if (typeof marked !== 'undefined') {
                marked.use({
                    breaks: true, // Converts single \n to <br>
                    gfm: true // Enables GitHub Flavored Markdown
                });
            }

            this.ui.init();
            this.uploader.init();
            this.prompts.init();
            this.interaction.init();

            // Expose for global access (e.g. onclick handlers)
            window.geminiApp = this;
        }

        refreshCsrf(hash) {
            if (!hash) return;
            this.config.csrfHash = hash;
            document.querySelectorAll(`input[name="${this.config.csrfName}"]`).forEach(el => el.value = hash);
        }

        async sendAjax(url, data = null) {
            const formData = data instanceof FormData ? data : new FormData();
            if (!formData.has(this.config.csrfName)) {
                formData.append(this.config.csrfName, this.config.csrfHash);
            }

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const responseData = await res.json();

                const newToken = responseData.token || responseData.csrf_token;
                if (newToken) this.refreshCsrf(newToken);

                return responseData;
            } catch (err) {
                console.error('AJAX Error:', err);
                this.ui.showToast('Network error occurred.');
                throw err;
            }
        }
    }

    class UIManager {
        constructor(app) {
            this.app = app;
            this.generateBtn = document.getElementById('generateBtn');
        }

        init() {
            this.setupTabs();
            this.setupSettings();
            this.setupCodeHighlighting();
            this.setupAutoScroll();
            this.setupDownloads();

            // Tinymce
            this.initEditor();
        }

        showToast(msg) {
            const t = document.getElementById('liveToast');
            if (t) {
                t.querySelector('.toast-body').textContent = msg;
                new bootstrap.Toast(t).show();
            }
        }

        initEditor() {
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
        }

        setupTabs() {
            const tabButtons = document.querySelectorAll('#generationTabs button[data-bs-toggle="tab"]');
            const modelInput = document.getElementById('selectedModelId');
            const typeInput = document.getElementById('generationType');
            const modelCards = document.querySelectorAll('.model-card');

            tabButtons.forEach(btn => {
                btn.addEventListener('shown.bs.tab', (e) => {
                    const type = e.target.dataset.type;
                    typeInput.value = type;
                    this.updateModelSelectionUI(type);
                });
            });

            modelCards.forEach(card => {
                card.addEventListener('click', () => {
                    modelCards.forEach(c => c.classList.remove('active'));
                    card.classList.add('active');
                    modelInput.value = card.dataset.model;
                });
            });
        }

        updateModelSelectionUI(type) {
            const area = document.getElementById('model-selection-area');
            const imgGrid = document.getElementById('image-models-grid');
            const vidGrid = document.getElementById('video-models-grid');
            const editor = tinymce.get('prompt');
            const modelInput = document.getElementById('selectedModelId');

            area.classList.add('d-none');
            imgGrid.classList.add('d-none');
            vidGrid.classList.add('d-none');

            if (type === 'text') {
                modelInput.value = 'gemini-2.0-flash';
                editor?.getBody().setAttribute('data-placeholder', 'Enter your prompt here...');
            } else {
                area.classList.remove('d-none');
                if (type === 'image') {
                    imgGrid.classList.remove('d-none');
                    editor?.getBody().setAttribute('data-placeholder', 'Describe the image you want to generate...');
                    // Auto-select first
                    const first = imgGrid.querySelector('.model-card');
                    if (first) first.click();
                } else if (type === 'video') {
                    vidGrid.classList.remove('d-none');
                    editor?.getBody().setAttribute('data-placeholder', 'Describe the video you want to create...');
                    const first = vidGrid.querySelector('.model-card');
                    if (first) first.click();
                }
            }
        }

        setupSettings() {
            document.querySelectorAll('.setting-toggle').forEach(toggle => {
                toggle.addEventListener('change', async (e) => {
                    const fd = new FormData();
                    fd.append('setting_key', e.target.dataset.key);
                    fd.append('enabled', e.target.checked);
                    try {
                        const data = await this.app.sendAjax(this.app.config.endpoints.settings, fd);
                        this.showToast(data.status === 'success' ? 'Setting saved.' : 'Failed to save.');
                    } catch (e) {}
                });
            });
        }

        setupCodeHighlighting() {
            if (typeof hljs !== 'undefined') hljs.highlightAll();

            document.querySelectorAll('pre code').forEach((block) => {
                if (block.parentElement.querySelector('.copy-code-btn')) return; // Avoid duplicates
                const btn = document.createElement('button');
                btn.className = 'btn btn-sm btn-dark copy-code-btn';
                btn.innerHTML = '<i class="bi bi-clipboard"></i>';
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    navigator.clipboard.writeText(block.innerText).then(() => {
                        btn.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
                        setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i>', 2000);
                    });
                });
                block.parentElement.appendChild(btn);
            });
        }

        ensureResultCardExists() {
            if (document.getElementById('results-card')) return;

            const container = document.querySelector('.gemini-view-container');
            const cardHtml = `
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
                <div class="card-body response-content" id="ai-response-body"></div>
                <textarea id="raw-response" class="d-none"></textarea>
                <div class="card-footer bg-transparent border-0 text-center">
                    <small class="text-muted fst-italic"><i class="bi bi-info-circle me-1"></i> AI can make mistakes. Please verify important information.</small>
                </div>
            </div>`;

            const row = container.querySelector('.row.g-4');
            row.insertAdjacentHTML('afterend', cardHtml);

            this.setupDownloads();
        }

        setupAutoScroll() {
            const resultsCard = document.getElementById('results-card');
            if (resultsCard) {
                setTimeout(() => resultsCard.scrollIntoView({
                    behavior: 'smooth'
                }), 100);
            }
        }

        setupDownloads() {
            document.querySelectorAll('.download-action').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    document.getElementById('dl_raw').value = document.getElementById('raw-response').value;
                    document.getElementById('dl_format').value = e.target.dataset.format;
                    document.getElementById('downloadForm').submit();
                });
            });
            const copyFull = document.getElementById('copyFullResponseBtn');
            if (copyFull) {
                copyFull.addEventListener('click', () => {
                    navigator.clipboard.writeText(document.getElementById('raw-response').value)
                        .then(() => this.showToast('Copied!'));
                });
            }
        }

        setLoading(isLoading, text = 'Processing...') {
            if (isLoading) {
                this.generateBtn.disabled = true;
                this.generateBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${text}`;
            } else {
                this.generateBtn.disabled = false;
                this.generateBtn.innerHTML = '<i class="bi bi-sparkles"></i> Generate';
            }
        }

        showMediaResult(url, type) {
            let container = document.getElementById('media-result-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'media-result-container';
                container.className = 'card blueprint-card mt-5 shadow-lg border-primary';
                document.querySelector('.container.my-3.my-lg-5').appendChild(container);
            }

            // Internal logic for download check
            const isInternal = url.includes('gemini/media/serve');
            const downloadAttr = isInternal ? `onclick="window.location.href='${url}${url.includes('?') ? '&' : '?'}download=1'"` : `onclick="geminiApp.interaction.mockDownload('${url}', '${type}')"`;

            container.innerHTML = `
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Studio Output</span>
                    <div>
                        <button ${downloadAttr} class="btn btn-sm btn-light" id="mediaDownloadBtn">
                            <i class="bi bi-download me-1"></i> Download
                        </button>
                    </div>
                </div>
                <div class="card-body text-center p-4">
                    ${type === 'image' 
                        ? `<img src="${url}" class="img-fluid rounded shadow-sm" alt="Generated Image">` 
                        : `<video controls autoplay loop class="w-100 rounded shadow-sm"><source src="${url}" type="video/mp4"></video>`
                    }
                </div>
            `;
            container.scrollIntoView({
                behavior: 'smooth'
            });
        }
    }

    class MediaUploader {
        constructor(app) {
            this.app = app;
            this.queue = [];
            this.isUploading = false;
        }

        init() {
            const area = document.getElementById('mediaUploadArea');
            const input = document.getElementById('media-input-trigger');

            if (area && input) {
                ['dragenter', 'dragover'].forEach(e => area.addEventListener(e, (ev) => {
                    ev.preventDefault();
                    area.classList.add('dragover');
                }));
                ['dragleave', 'drop'].forEach(e => area.addEventListener(e, (ev) => {
                    ev.preventDefault();
                    area.classList.remove('dragover');
                }));

                area.addEventListener('drop', (e) => this.handleFiles(e.dataTransfer.files));
                input.addEventListener('change', (e) => this.handleFiles(e.target.files));

                const listWrapper = document.getElementById('upload-list-wrapper');
                if (listWrapper) {
                    listWrapper.addEventListener('click', (e) => {
                        if (e.target.closest('.remove-btn')) this.removeFile(e.target.closest('.remove-btn'));
                    });
                }
            }
        }

        handleFiles(files) {
            const currentCount = document.querySelectorAll('input[name="uploaded_media[]"]').length + this.queue.length;
            if (currentCount + files.length > this.app.config.maxFiles) {
                this.app.ui.showToast(`Max ${this.app.config.maxFiles} files allowed.`);
                return;
            }

            Array.from(files).forEach(file => {
                if (file.size > this.app.config.maxFileSize) {
                    this.app.ui.showToast(`${file.name} too large.`);
                    return;
                }
                const id = Math.random().toString(36).substr(2, 9);
                const ui = this.createProgressBar(file, id);
                this.queue.push({
                    file,
                    ui,
                    id
                });
            });

            this.processQueue();
        }

        createProgressBar(file, id) {
            const div = document.createElement('div');
            div.id = `file-item-${id}`;
            div.className = 'file-item d-flex align-items-center gap-3 rounded p-2 mb-2';
            div.innerHTML = `
                <div class="flex-grow-1" style="min-width: 0;">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-bold text-truncate">${file.name}</span>
                        <span class="status-text text-muted">Waiting...</span>
                    </div>
                    <div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div></div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger border-0 remove-btn disabled" data-id="${id}"><i class="bi bi-x-lg"></i></button>
            `;
            document.getElementById('upload-list-wrapper').appendChild(div);
            return div;
        }

        processQueue() {
            if (this.isUploading || this.queue.length === 0) return;
            this.isUploading = true;
            this.performUpload(this.queue.shift());
        }

        performUpload(job) {
            job.ui.querySelector('.status-text').textContent = "Uploading...";
            const fd = new FormData();
            fd.append(this.app.config.csrfName, this.app.config.csrfHash);
            fd.append('file', job.file);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.app.config.endpoints.upload, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const pct = Math.round((e.loaded / e.total) * 100);
                    job.ui.querySelector('.progress-bar').style.width = pct + '%';
                }
            });

            xhr.onreadystatechange = () => {
                if (xhr.readyState === 4) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.csrf_token) this.app.refreshCsrf(res.csrf_token);

                        if (xhr.status === 200 && res.status === 'success') {
                            this.updateUI(job.ui, 'success');
                            job.ui.querySelector('.remove-btn').dataset.serverFileId = res.file_id;

                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'uploaded_media[]';
                            input.value = res.file_id;
                            input.id = `input-${job.id}`;
                            document.getElementById('uploaded-files-container').appendChild(input);
                        } else {
                            this.updateUI(job.ui, 'error', res.message);
                        }
                    } catch (e) {
                        this.updateUI(job.ui, 'error', 'JSON Error');
                    }

                    this.isUploading = false;
                    this.processQueue();
                }
            };
            xhr.send(fd);
        }

        updateUI(ui, status, msg = '') {
            const bar = ui.querySelector('.progress-bar');
            const txt = ui.querySelector('.status-text');
            bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            ui.querySelector('.remove-btn').classList.remove('disabled');

            if (status === 'success') {
                bar.classList.add('bg-success');
                txt.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
            } else {
                bar.classList.add('bg-danger');
                bar.style.width = '100%';
                txt.innerHTML = `<span class="text-danger small" title="${msg}">${msg || 'Failed'}</span>`;
            }
        }

        async removeFile(btn) {
            if (btn.classList.contains('disabled')) return;
            const ui = btn.closest('.file-item');
            const serverId = btn.dataset.serverFileId;

            ui.style.opacity = '0.5';
            if (serverId) {
                const fd = new FormData();
                fd.append('file_id', serverId);
                try {
                    const res = await this.app.sendAjax(this.app.config.endpoints.deleteMedia, fd);
                    if (res.status === 'success') {
                        ui.remove();
                        document.getElementById(`input-${btn.dataset.id}`)?.remove();
                    } else ui.style.opacity = '1';
                } catch (e) {
                    ui.style.opacity = '1';
                }
            } else {
                ui.remove();
            }
        }
    }

    class PromptManager {
        constructor(app) {
            this.app = app;
        }

        init() {
            const select = document.getElementById('savedPrompts');
            const loadBtn = document.getElementById('usePromptBtn');
            const deleteBtn = document.getElementById('deletePromptBtn');

            if (loadBtn) loadBtn.addEventListener('click', () => {
                if (select && select.value) tinymce.get('prompt').setContent(select.value);
            });

            if (select) select.addEventListener('change', () => {
                if (deleteBtn) deleteBtn.disabled = !select.value;
            });

            if (deleteBtn) deleteBtn.addEventListener('click', () => this.deletePrompt());

            // Save Prompt Modal
            const form = document.querySelector('#savePromptModal form');
            if (form) {
                document.getElementById('savePromptModal').addEventListener('show.bs.modal', () => {
                    document.getElementById('modalPromptText').value = tinymce.get('prompt').getContent({
                        format: 'text'
                    });
                });

                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.savePrompt(new FormData(form));
                });
            }
        }

        async savePrompt(formData) {
            const modalEl = document.getElementById('savePromptModal');
            const modal = bootstrap.Modal.getInstance(modalEl);

            try {
                const data = await this.app.sendAjax(formData.get('action') || modalEl.querySelector('form').action, formData);
                if (data.status === 'success') {
                    this.app.ui.showToast('Saved!');
                    modal.hide();
                    location.reload();
                } else {
                    this.app.ui.showToast(data.message || 'Failed');
                }
            } catch (e) {}
        }

        async deletePrompt() {
            const select = document.getElementById('savedPrompts');
            const id = select.options[select.selectedIndex].dataset.id;
            if (confirm('Delete this prompt?')) {
                try {
                    const data = await this.app.sendAjax(this.app.config.endpoints.deletePromptBase + id);
                    if (data.status === 'success') {
                        this.app.ui.showToast('Deleted');
                        select.options[select.selectedIndex].remove();
                        select.value = '';
                        select.dispatchEvent(new Event('change'));
                    }
                } catch (e) {}
            }
        }
    }

    class InteractionHandler {
        constructor(app) {
            this.app = app;
        }

        init() {
            const form = document.getElementById('geminiForm');
            if (form) form.addEventListener('submit', (e) => this.handleSubmit(e));
        }

        async handleSubmit(e) {
            const type = document.getElementById('generationType').value;
            const useStreaming = document.getElementById('streamOutput')?.checked;

            // Text + Standard POST -> Let browser handle it
            if (type === 'text' && !useStreaming) {
                this.app.ui.setLoading(true, 'Thinking...');
                return;
            }

            e.preventDefault();

            const prompt = tinymce.get('prompt').getContent({
                format: 'text'
            }).trim();
            if (!prompt && type === 'text') {
                this.app.ui.showToast('Please enter a prompt.');
                return;
            }

            this.app.ui.setLoading(true);
            const fd = new FormData(document.getElementById('geminiForm'));
            // Tinymce not syncing automatically sometimes on manual submit intercept
            fd.set('prompt', prompt);

            if (type === 'text' && useStreaming) {
                await this.handleStreaming(fd);
            } else {
                // Media (Image/Video) or Mock
                if (this.handleMock(prompt, type)) return;
                await this.handleMedia(fd);
            }
        }

        async handleStreaming(formData) {
            this.app.ui.ensureResultCardExists();

            const resBody = document.getElementById('ai-response-body');
            const rawRes = document.getElementById('raw-response');
            resBody.innerHTML = '';
            rawRes.value = '';
            this.app.ui.setupAutoScroll();

            let streamAccumulator = '';

            try {
                const response = await fetch(this.app.config.endpoints.stream, {
                    method: 'POST',
                    body: formData
                });

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const {
                        value,
                        done
                    } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, {
                        stream: true
                    });
                    const parts = buffer.split('\n\n');
                    buffer = parts.pop();

                    for (const part of parts) {
                        if (part.startsWith('data: ')) {
                            try {
                                const data = JSON.parse(part.substring(6));
                                if (data.text) {
                                    streamAccumulator += data.text;
                                    resBody.innerHTML = marked.parse(streamAccumulator);
                                    rawRes.value += data.text;
                                } else if (data.error) {
                                    this.app.ui.showToast(data.error);
                                } else if (data.csrf_token) {
                                    this.app.refreshCsrf(data.csrf_token);
                                }
                            } catch (e) {}
                        }
                    }
                }

                this.app.ui.setupCodeHighlighting();

            } catch (e) {
                this.app.ui.showToast('Stream error');
            } finally {
                this.app.ui.setLoading(false);
            }
        }

        async handleMedia(formData) {
            try {
                const res = await fetch(this.app.config.endpoints.generateMedia, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                const data = await res.json();
                if (data.token) this.app.refreshCsrf(data.token);

                if (data.status === 'error') {
                    this.app.ui.showToast(data.message);
                } else if (data.type === 'image') {
                    this.app.ui.showMediaResult(data.url, 'image');
                } else if (data.type === 'video') {
                    this.pollVideo(data.op_id);
                    return; // Don't reset loading yet
                }
            } catch (e) {
                console.error(e);
                this.app.ui.showToast('Media generation failed.');
            }
            this.app.ui.setLoading(false);
        }

        pollVideo(opId) {
            this.app.ui.generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Compositing Video...';
            const timer = setInterval(async () => {
                const fd = new FormData();
                fd.append('op_id', opId);
                try {
                    const res = await this.app.sendAjax(this.app.config.endpoints.pollMedia, fd);
                    if (res.status === 'completed') {
                        clearInterval(timer);
                        this.app.ui.showMediaResult(res.url, 'video');
                        this.app.ui.setLoading(false);
                    } else if (res.status === 'failed') {
                        clearInterval(timer);
                        this.app.ui.showToast(res.message);
                        this.app.ui.setLoading(false);
                    }
                } catch (e) {}
            }, 5000);
        }

        handleMock(prompt, type) {
            const p = prompt.toLowerCase();
            if (p === 'test image' && type === 'image') {
                setTimeout(() => {
                    this.app.ui.showMediaResult('https://picsum.photos/200/300?random=' + Date.now(), 'image');
                    this.app.ui.setLoading(false);
                }, 1000);
                return true;
            }
            if (p === 'test video' && type === 'video') {
                setTimeout(() => {
                    this.app.ui.showMediaResult('http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', 'video');
                    this.app.ui.setLoading(false);
                }, 1500);
                return true;
            }
            return false;
        }

        async mockDownload(url, type) {
            const btn = document.getElementById('mediaDownloadBtn');
            if (!btn) return;
            const original = btn.innerHTML;
            btn.innerHTML = 'Downloading...';

            try {
                const resp = await fetch(url);
                const blob = await resp.blob();
                const u = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = u;
                a.download = `mock_${type}_${Date.now()}.${type==='video'?'mp4':'jpg'}`;
                document.body.appendChild(a);
                a.click();
                a.remove();
            } catch (e) {
                this.app.ui.showToast('Download failed');
            }
            btn.innerHTML = original;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        new GeminiApp().init();
    });
</script>
<?= $this->endSection() ?>