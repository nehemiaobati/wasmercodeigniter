<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('public/assets/highlight/styles/atom-one-dark.min.css') ?>">
<style>
    /* Hide Global Nav & Footer */
    #mainNavbar,
    .footer,
    .container.my-4 {
        display: none !important;
    }

    body {
        overflow: hidden;
        /* Important for sticky layout */
        padding: 0 !important;
    }

    /* Scoped Styles for Gemini View */
    .gemini-view-container {
        --code-bg: #282c34;
        position: fixed;
        /* Lock to viewport to prevent body scroll on input focus */
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        height: 100dvh;
        /* Mobile browser address bar fix */
        width: 100vw;
        display: flex;
        overflow: hidden;
        z-index: 1000;
    }

    /* Main Content Area */
    .gemini-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
        min-width: 0;
        overflow: hidden;
        /* Prevent double scrollbars */
    }

    /* Response / History Area */
    .gemini-response-area {
        flex: 1;
        overflow-y: auto;
        padding: 2rem;
        scroll-behavior: smooth;
        min-height: 0;
        /* Allow shrinking in flex container */
    }

    /* Sticky Header */
    .gemini-header {
        position: sticky;
        top: 0;
        z-index: 1020;
        background: var(--bs-body-bg);
    }

    /* Prompt Area (Sticky Bottom) */
    .gemini-prompt-area {
        width: 100%;
        background: var(--bs-body-bg);
        border-top: 1px solid var(--bs-border-color);
        padding: 1.5rem;
        z-index: 10;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
    }

    /* Settings Sidebar */
    .gemini-sidebar {
        width: 350px;
        border-left: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
        overflow-y: auto;
        height: 100%;
        padding: 1.5rem;
        transition: width 0.3s ease, padding 0.3s ease;
    }

    .gemini-sidebar.collapse:not(.show) {
        display: none;
    }

    /* Responsive Adjustments */
    @media (max-width: 991.98px) {
        .gemini-sidebar {
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            z-index: 1050;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
        }
    }

    /* Utilities */
    .prompt-card {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        margin-bottom: 0;
    }

    .prompt-editor-wrapper {
        min-height: 100px;
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        background: var(--bs-body-bg);
    }

    /* Code Block Styling */
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

    /* Upload Area */
    #mediaUploadArea {
        border: 2px dashed var(--bs-border-color);
        padding: 1rem;
        background: var(--bs-tertiary-bg);
        transition: 0.2s;
        border-radius: 0.5rem;
    }

    #mediaUploadArea.dragover {
        background: var(--bs-primary-bg-subtle);
        border-color: var(--bs-primary);
    }

    /* Model Selection */
    .model-card {
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }

    .model-card:hover {
        border-color: var(--bs-primary);
        transform: translateY(-2px);
    }

    .model-card.active {
        border-color: var(--bs-primary);
        background-color: var(--bs-primary-bg-subtle);
    }

    /* Toast */
    .gemini-toast-container {
        right: 20px;
        top: 20px;
        bottom: auto;
        transform: none;
        z-index: 1060;
    }

    /* New Upload Chips Styles */
    #upload-list-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        max-height: 100px;
        overflow-y: auto;
        margin-bottom: 0.5rem;
    }

    .file-chip {
        display: flex;
        align-items: center;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 0.85rem;
        max-width: 220px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .file-chip .file-name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-right: 8px;
        max-width: 150px;
    }

    .file-chip .progress-ring {
        width: 16px;
        height: 16px;
        margin-right: 8px;
        border: 2px solid var(--bs-secondary-bg);
        border-top: 2px solid var(--bs-primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .file-chip .remove-btn {
        transition: opacity 0.2s;
    }

    .file-chip .remove-btn:not(.disabled):hover {
        opacity: 1 !important;
    }

    /* Auto-expanding Textarea */
    .prompt-textarea {
        resize: none;
        overflow-y: hidden;
        /* Hide scrollbar initially */
        min-height: 40px;
        max-height: 120px;
        /* Approx 4-5 lines */
        border-radius: 1.5rem;
        padding: 0.6rem 1rem;
        transition: border-color 0.2s;
        line-height: 1.5;
    }

    .prompt-textarea:focus {
        box-shadow: none;
        border-color: var(--bs-primary);
    }

    /* Prompt Area Layout Adjustment */
    .gemini-prompt-area {
        padding: 1rem 1.5rem;
        padding-bottom: calc(1rem + env(safe-area-inset-bottom));
        /* iOS Safe Area */
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="gemini-view-container">

    <!-- Main Content (Left/Center) -->
    <div class="gemini-main">
        <!-- Top Toolbar / Header -->
        <div class="d-flex justify-content-between align-items-center px-4 py-2 border-bottom bg-body gemini-header">
            <a href="<?= url_to('home') ?>" class="d-flex align-items-center gap-2 text-decoration-none text-reset">
                <i class="bi bi-stars text-primary fs-4"></i>
                <span class="fw-bold fs-5">AI Studio</span>
            </a>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm theme-toggle" type="button" aria-label="Toggle theme">
                    <i class="bi bi-circle-half"></i>
                </button>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#geminiSidebar" aria-expanded="true" aria-controls="geminiSidebar">
                    <i class="bi bi-layout-sidebar-reverse"></i> Settings
                </button>
            </div>
        </div>

        <!-- Scrollable Response Area -->
        <div class="gemini-response-area" id="response-area-wrapper">

            <!-- Flash Messages Container for AJAX Injection -->
            <div id="flash-messages-container">
                <?= view('App\Views\partials\flash_messages') ?>
            </div>

            <!-- Audio Player Container (Dynamically Populated via AJAX) -->
            <div id="audio-player-container">
                <?php
                // REFACTOR: Check audio_url instead of base64 for session hygiene
                if (session()->getFlashdata('audio_url')): ?>
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-volume-up-fill fs-4 me-3"></i>
                        <audio controls autoplay class="w-100">
                            <!-- Use the served URL -->
                            <source src="<?= url_to('gemini.serve_audio', session()->getFlashdata('audio_url')) ?>">
                        </audio>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Response -->
            <?php if ($result = session()->getFlashdata('result')): ?>
                <div class="card blueprint-card shadow-sm border-primary" id="results-card">
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
            <?php else: ?>
                <div class="text-center text-muted mt-5 pt-5" id="empty-state">
                    <div class="display-1 text-body-tertiary mb-3"><i class="bi bi-lightbulb"></i></div>
                    <h5>Start Creating</h5>
                    <p>Enter your prompt below to generate text, images, or code.</p>
                </div>
            <?php endif; ?>

        </div>

        <!-- Sticky Prompt Area -->
        <div class="gemini-prompt-area">
            <form id="geminiForm" action="<?= url_to('gemini.generate') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <!-- Tabs -->
                <ul class="nav nav-pills nav-sm mb-2" id="generationTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-2 px-3" id="text-tab" data-bs-toggle="tab" data-bs-target="#text-pane" type="button" role="tab" data-type="text" data-model="gemini-2.5-flash">
                            <i class="bi bi-chat-text me-2"></i>Text
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2 px-3" id="image-tab" data-bs-toggle="tab" data-bs-target="#image-pane" type="button" role="tab" data-type="image">
                            <i class="bi bi-image me-2"></i>Image
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2 px-3" id="video-tab" data-bs-toggle="tab" data-bs-target="#video-pane" type="button" role="tab" data-type="video">
                            <i class="bi bi-camera-video me-2"></i>Video
                        </button>
                    </li>
                </ul>

                <!-- Model Selection (Hidden by default, toggles based on tab) -->
                <div id="model-selection-area" class="mb-2 d-none">
                    <div id="image-models-grid" class="d-flex gap-2 d-none overflow-auto py-2">
                        <?php if (!empty($mediaConfigs)): ?>
                            <?php foreach ($mediaConfigs as $modelId => $config): ?>
                                <?php if (strpos($config['type'], 'image') !== false): ?>
                                    <div class="model-card card p-2" style="min-width: 120px;" data-model="<?= esc($modelId) ?>" data-type="image">
                                        <div class="text-center small">
                                            <i class="bi bi-image fs-5 text-primary"></i>
                                            <div class="text-truncate mt-1"><?= esc($config['name']) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div id="video-models-grid" class="d-flex gap-2 d-none overflow-auto py-2">
                        <?php if (!empty($mediaConfigs)): ?>
                            <?php foreach ($mediaConfigs as $modelId => $config): ?>
                                <?php if ($config['type'] === 'video'): ?>
                                    <div class="model-card card p-2" style="min-width: 120px;" data-model="<?= esc($modelId) ?>" data-type="video">
                                        <div class="text-center small">
                                            <i class="bi bi-camera-video fs-5 text-danger"></i>
                                            <div class="text-truncate mt-1"><?= esc($config['name']) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Uploads List (Moved above input) -->
                <div id="upload-list-wrapper"></div>
                <div id="uploaded-files-container"></div>

                <div class="d-flex align-items-end gap-2 bg-body-tertiary p-2 rounded-4 border">
                    <!-- Attachment Button -->
                    <div id="mediaUploadArea" class="d-inline-block p-0 border-0 bg-transparent mb-1">
                        <input type="file" id="media-input-trigger" multiple class="d-none">
                        <label for="media-input-trigger" class="btn btn-link text-secondary p-1" title="Attach files">
                            <i class="bi bi-paperclip fs-4"></i>
                        </label>
                    </div>

                    <!-- Textarea -->
                    <div class="flex-grow-1">
                        <input type="hidden" name="model_id" id="selectedModelId" value="gemini-2.0-flash">
                        <input type="hidden" name="generation_type" id="generationType" value="text">
                        <textarea
                            id="prompt"
                            name="prompt"
                            class="form-control border-0 bg-transparent prompt-textarea shadow-none"
                            placeholder="Message Gemini..."
                            rows="1"><?= old('prompt') ?></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <button type="button" class="btn btn-link text-secondary p-1" data-bs-toggle="modal" data-bs-target="#savePromptModal" title="Save Prompt">
                            <i class="bi bi-bookmark-plus fs-5"></i>
                        </button>
                        <button type="submit" id="generateBtn" class="btn btn-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" title="Send">
                            <i class="bi bi-arrow-up text-white fs-5"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Sidebar (Settings) -->
    <div class="gemini-sidebar collapse collapse-horizontal show" id="geminiSidebar">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold m-0"><i class="bi bi-sliders"></i> Configuration</h5>
            <button type="button" class="btn-close d-lg-none" data-bs-toggle="collapse" data-bs-target="#geminiSidebar"></button>
        </div>

        <!-- Toggles -->
        <div class="form-check form-switch mb-3">
            <input class="form-check-input setting-toggle" type="checkbox" id="assistantMode"
                data-key="assistant_mode_enabled" <?= $assistant_mode_enabled ? 'checked' : '' ?>>
            <label class="form-check-label fw-medium" for="assistantMode">Conversational Memory</label>
            <div class="form-text text-muted small lh-sm">
                Maintains context from previous messages.
            </div>
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input setting-toggle" type="checkbox" id="voiceOutput"
                data-key="voice_output_enabled" <?= $voice_output_enabled ? 'checked' : '' ?>>
            <label class="form-check-label fw-medium" for="voiceOutput">Voice Output (TTS)</label>
            <div class="form-text text-muted small lh-sm">
                Reads the response aloud.
            </div>
        </div>
        <div class="form-check form-switch mb-4">
            <input class="form-check-input setting-toggle" type="checkbox" id="streamOutput"
                data-key="stream_output_enabled" <?= $stream_output_enabled ? 'checked' : '' ?>>
            <label class="form-check-label fw-medium" for="streamOutput">Stream Responses</label>
            <div class="form-text text-muted small lh-sm">
                Typewriter effect (faster perception).
            </div>
        </div>

        <hr>

        <!-- Saved Prompts -->
        <label class="form-label small fw-bold text-uppercase text-muted">Saved Prompts</label>
        <div id="saved-prompts-wrapper">
            <?php if (!empty($prompts)): ?>
                <div class="input-group mb-3">
                    <select class="form-select form-select-sm" id="savedPrompts">
                        <option value="" disabled selected>Select...</option>
                        <?php foreach ($prompts as $p): ?>
                            <option value="<?= esc($p->prompt_text, 'attr') ?>" data-id="<?= $p->id ?>"><?= esc($p->title) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="usePromptBtn">Load</button>
                    <button class="btn btn-outline-danger btn-sm" type="button" id="deletePromptBtn" disabled>
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            <?php else: ?>
                <div class="alert alert-light border mb-3 small text-muted">
                    No saved prompts yet.
                </div>
            <?php endif; ?>
        </div>

        <!-- Clear Memory -->
        <hr>
        <form action="<?= url_to('gemini.memory.clear') ?>" method="post" onsubmit="return confirm('Are you sure? This cannot be undone.');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                <i class="bi bi-trash me-2"></i> Clear History
            </button>
        </form>
    </div>
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
                supportedMimeTypes: <?= $supportedMimeTypes ?>,
                endpoints: {
                    upload: '<?= url_to('gemini.upload_media') ?>',
                    deleteMedia: '<?= url_to('gemini.delete_media') ?>',
                    settings: '<?= url_to('gemini.settings.update') ?>',
                    deletePromptBase: '<?= url_to('gemini.prompts.delete', 0) ?>'.slice(0, -1),
                    stream: '<?= url_to('gemini.stream') ?>',
                    generate: '<?= url_to('gemini.generate') ?>',
                    generateMedia: '<?= url_to('gemini.media.generate') ?>',
                    pollMedia: '<?= url_to('gemini.media.poll') ?>',
                    // No longer needing serveAudio for core generation, but keeping safe route generation
                    serveAudio: '<?= url_to('gemini.serve_audio', 'placeholder') ?>'.replace('placeholder', '')
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
            // setupTabs and AutoTextarea
            this.setupTabs();
            this.setupSettings();
            this.setupCodeHighlighting();
            this.setupAutoScroll();
            this.setupDownloads();
            this.initTinyMCE();
        }

        initTinyMCE() {
            if (typeof tinymce === 'undefined') return;

            tinymce.init({
                selector: '#prompt',
                menubar: false,
                statusbar: false,
                toolbar: false,
                license_key: 'gpl',
                plugins: 'autoresize',
                autoresize_bottom_margin: 0,
                autoresize_overflow_padding: 0,
                min_height: 40,
                max_height: 120, // Approx 4-5 lines

                setup: (editor) => {
                    editor.on('keydown', (e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            if (editor.getContent().trim()) {
                                editor.save(); // Sync content to textarea
                                document.getElementById('geminiForm').requestSubmit();
                            }
                        }
                    });

                    // Update model placeholder
                    editor.on('init', () => {
                        const currentType = document.getElementById('generationType').value;
                        this.updateModelSelectionUI(currentType);
                    });
                }
            });
        }

        showToast(msg) {
            const t = document.getElementById('liveToast');
            if (t) {
                t.querySelector('.toast-body').textContent = msg;
                new bootstrap.Toast(t).show();
            }
        }

        // Helper to inject error messages into the flash container (for Consistency)
        injectFlashError(message) {
            const container = document.getElementById('flash-messages-container');
            if (container) {
                container.innerHTML = `<div class="alert alert-danger alert-dismissible fade show">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
            }
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
            const editor = document.getElementById('prompt');
            const modelInput = document.getElementById('selectedModelId');

            area.classList.add('d-none');
            imgGrid.classList.add('d-none');
            vidGrid.classList.add('d-none');

            if (type === 'text') {
                modelInput.value = 'gemini-2.0-flash';
                if (tinymce.activeEditor) tinymce.activeEditor.getBody().setAttribute('data-mce-placeholder', 'Message Gemini...');
                else editor?.setAttribute('placeholder', 'Message Gemini...');
            } else {
                area.classList.remove('d-none');
                if (type === 'image') {
                    imgGrid.classList.remove('d-none');
                    imgGrid.classList.remove('d-none');
                    if (tinymce.activeEditor) tinymce.activeEditor.getBody().setAttribute('data-mce-placeholder', 'Describe the image you want to generate...');
                    else editor?.setAttribute('placeholder', 'Describe the image you want to generate...');
                    // Auto-select first
                    const first = imgGrid.querySelector('.model-card');
                    if (first) first.click();
                } else if (type === 'video') {
                    vidGrid.classList.remove('d-none');
                    vidGrid.classList.remove('d-none');
                    if (tinymce.activeEditor) tinymce.activeEditor.getBody().setAttribute('data-mce-placeholder', 'Describe the video you want to create...');
                    else editor?.setAttribute('placeholder', 'Describe the video you want to create...');
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
                        // Using Toast for settings toggle confirmation (Consistency Requirement)
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

            const row = document.getElementById('response-area-wrapper');
            row.insertAdjacentHTML('beforeend', cardHtml);

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
                this.generateBtn.innerHTML = `<span class="spinner-border spinner-border-sm text-white" role="status" aria-hidden="true"></span>`;
            } else {
                this.generateBtn.disabled = false;
                this.generateBtn.innerHTML = '<i class="bi bi-arrow-up text-white fs-5"></i>';
            }
        }

        showMediaResult(url, type) {
            let container = document.getElementById('media-result-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'media-result-container';
                container.className = 'card blueprint-card mt-5 shadow-lg border-primary';
                document.getElementById('response-area-wrapper').appendChild(container);
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

            let accepted = 0;
            let rejected = {
                limit: 0,
                type: 0,
                size: 0
            };

            Array.from(files).forEach(file => {
                // 1. Check file limit
                if (currentCount + accepted >= this.app.config.maxFiles) {
                    rejected.limit++;
                    if (files.length === 1) {
                        this.app.ui.showToast(`Max ${this.app.config.maxFiles} files allowed`);
                    }
                    return;
                }

                // 2. Check MIME type
                if (!this.app.config.supportedMimeTypes.includes(file.type)) {
                    rejected.type++;
                    this.app.ui.showToast(`${file.name}: Unsupported file type`);
                    return;
                }

                // 3. Check file size
                if (file.size > this.app.config.maxFileSize) {
                    rejected.size++;
                    this.app.ui.showToast(`${file.name} exceeds ${(this.app.config.maxFileSize / 1024 / 1024).toFixed(1)}MB limit`);
                    return;
                }

                // Queue valid file
                const id = Math.random().toString(36).substr(2, 9);
                const ui = this.createProgressBar(file, id);
                this.queue.push({
                    file,
                    ui,
                    id
                });
                accepted++;
            });

            // Show summary for batch uploads
            if (files.length > 1) {
                const total = rejected.limit + rejected.type + rejected.size;
                if (total > 0) {
                    this.app.ui.showToast(`${accepted} uploaded, ${total} rejected`);
                }
            }

            if (this.queue.length > 0) {
                this.processQueue();
            }
        }

        createProgressBar(file, id) {
            const div = document.createElement('div');
            div.id = `file-item-${id}`;
            div.className = 'file-chip fade show';
            div.innerHTML = `
                <div class="progress-ring"></div>
                <span class="file-name" title=""></span>
                <button type="button" class="btn-close p-1 remove-btn disabled" style="width: 0.75rem; height: 0.75rem; opacity: 0.6;" data-id="${id}"></button>
            `;
            const span = div.querySelector('.file-name');
            span.textContent = file.name;
            span.title = file.name;
            document.getElementById('upload-list-wrapper').appendChild(div);
            return div;
        }

        processQueue() {
            if (this.isUploading || this.queue.length === 0) return;
            this.isUploading = true;
            this.performUpload(this.queue.shift());
        }

        performUpload(job) {
            // job.ui is the .file-chip div
            const fd = new FormData();
            fd.append(this.app.config.csrfName, this.app.config.csrfHash);
            fd.append('file', job.file);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.app.config.endpoints.upload, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onreadystatechange = () => {
                if (xhr.readyState === 4) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.csrf_token) this.app.refreshCsrf(res.csrf_token);

                        if (xhr.status === 200 && res.status === 'success') {
                            this.updateUI(job.ui, 'success');
                            const removeBtn = job.ui.querySelector('.remove-btn');
                            removeBtn.dataset.serverFileId = res.file_id;
                            removeBtn.dataset.id = job.id;

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
            const spinner = ui.querySelector('.progress-ring');
            const btn = ui.querySelector('.remove-btn');

            if (spinner) spinner.remove();
            btn.classList.remove('disabled');

            if (status === 'success') {
                // Add checkmark icon
                const icon = document.createElement('i');
                icon.className = 'bi bi-check-circle-fill text-success me-2';
                ui.insertBefore(icon, ui.firstChild);
                ui.style.borderColor = 'var(--bs-success)';
            } else {
                const icon = document.createElement('i');
                icon.className = 'bi bi-exclamation-circle-fill text-danger me-2';
                ui.insertBefore(icon, ui.firstChild);
                ui.style.borderColor = 'var(--bs-danger)';
                ui.title = msg;
            }
        }

        async removeFile(btn) {
            if (btn.classList.contains('disabled')) return;
            const ui = btn.closest('.file-chip');
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
                if (select && select.value) {
                    const el = document.getElementById('prompt');
                    // Check for TinyMCE instance
                    if (typeof tinymce !== 'undefined' && tinymce.get('prompt')) {
                        tinymce.get('prompt').setContent(select.value);
                    } else {
                        // Fallback for standard textarea
                        el.value = select.value;
                        el.focus();
                    }
                    el.dispatchEvent(new Event('input')); // Trigger resize if any
                }
            });

            if (select) select.addEventListener('change', () => {
                if (deleteBtn) deleteBtn.disabled = !select.value;
            });

            if (deleteBtn) deleteBtn.addEventListener('click', () => this.deletePrompt());

            // Save Prompt Modal
            const form = document.querySelector('#savePromptModal form');
            if (form) {
                document.getElementById('savePromptModal').addEventListener('show.bs.modal', () => {
                    const currentVal = (typeof tinymce !== 'undefined' && tinymce.get('prompt')) ?
                        tinymce.get('prompt').getContent() :
                        document.getElementById('prompt').value;
                    document.getElementById('modalPromptText').value = currentVal;
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
            const title = formData.get('title');

            try {
                const data = await this.app.sendAjax(formData.get('action') || modalEl.querySelector('form').action, formData);

                if (data.status === 'success') {
                    this.app.ui.showToast('Saved!');
                    modal.hide();

                    // Dynamic UI Update (No Reload)
                    if (data.prompt && data.prompt.id) {
                        const select = document.getElementById('savedPrompts');
                        const option = document.createElement('option');
                        option.value = data.prompt.prompt_text;
                        option.textContent = data.prompt.title;
                        option.dataset.id = data.prompt.id;
                        select.appendChild(option);
                        select.value = data.prompt.prompt_text;

                        // Enable delete button
                        const deleteBtn = document.getElementById('deletePromptBtn');
                        if (deleteBtn) deleteBtn.disabled = false;
                    }
                } else {
                    this.app.ui.showToast(data.message || 'Failed');
                }
            } catch (e) {
                console.error(e);
                this.app.ui.showToast('Error saving prompt');
            }
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
            e.preventDefault();
            const type = document.getElementById('generationType').value;
            const useStreaming = document.getElementById('streamOutput')?.checked;

            if (typeof tinymce !== 'undefined') tinymce.triggerSave();
            const prompt = document.getElementById('prompt').value.trim();
            if (!prompt && type === 'text') {
                this.app.ui.showToast('Please enter a prompt.');
                return;
            }

            this.app.ui.setLoading(true);
            const fd = new FormData(document.getElementById('geminiForm'));

            // Text Generation: Determine flow based on Streaming setting
            if (type === 'text') {
                if (useStreaming) {
                    await this.handleStreaming(fd);
                } else {
                    await this.handleStandardGeneration(fd);
                }
                return;
            }

            // Media Generation
            if (this.handleMock(prompt, type)) return;
            await this.handleMedia(fd);
        }

        /**
         * Handles standard (non-streaming) text generation via Fetch.
         * Updates content and injects flash messages via AJAX.
         * Explicitly handles audio rendering if audio_url is present.
         */
        async handleStandardGeneration(formData) {
            this.app.ui.ensureResultCardExists();

            try {
                const data = await this.app.sendAjax(this.app.config.endpoints.generate, formData);

                if (data.status === 'success') {
                    // Update Content
                    document.getElementById('ai-response-body').innerHTML = data.result;
                    document.getElementById('raw-response').value = data.raw_result;
                    this.app.ui.setupCodeHighlighting();
                    this.app.ui.setupAutoScroll();

                    // Inject Flash Messages (Standardized)
                    if (data.flash_html) {
                        const flashContainer = document.getElementById('flash-messages-container');
                        if (flashContainer) {
                            flashContainer.innerHTML = data.flash_html;
                        }
                    }

                    // FIX: Handle Audio URL injection dynamically for visibility
                    const audioContainer = document.getElementById('audio-player-container');
                    if (data.audio_url) {
                        if (audioContainer) {
                            audioContainer.innerHTML = `
                                <div class="alert alert-info d-flex align-items-center mb-4">
                                    <i class="bi bi-volume-up-fill fs-4 me-3"></i>
                                    <audio controls autoplay class="w-100">
                                        <source src="${data.audio_url}" type="audio/mpeg">
                                    </audio>
                                </div>
                            `;
                        }
                    } else {
                        // Clear audio container if no audio in this response
                        if (audioContainer) audioContainer.innerHTML = '';
                    }

                } else if (data.status === 'error') {
                    // REFACTOR: Correctly handle empty errors array vs global message
                    let errorHtml = '<div class="alert alert-danger alert-dismissible fade show">';

                    if (data.errors && Object.keys(data.errors).length > 0) {
                        // Handle Validation Errors
                        if (Array.isArray(data.errors)) {
                            errorHtml += data.errors.join('<br>');
                        } else {
                            errorHtml += Object.values(data.errors).join('<br>');
                        }
                    } else {
                        // Handle Single Global Message (e.g. Quota Exceeded)
                        errorHtml += data.message;
                    }

                    errorHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';

                    const flashContainer = document.getElementById('flash-messages-container');
                    if (flashContainer) {
                        flashContainer.innerHTML = errorHtml;
                    }
                }

            } catch (e) {
                console.error(e);
                this.app.ui.injectFlashError('Generation failed due to a system error.');
            } finally {
                this.app.ui.setLoading(false);
            }
        }

        async handleStreaming(formData) {
            this.app.ui.ensureResultCardExists();

            // Reset UI for new stream
            const resBody = document.getElementById('ai-response-body');
            const rawRes = document.getElementById('raw-response');
            // Clear audio container on new stream start
            const audioContainer = document.getElementById('audio-player-container');
            if (audioContainer) audioContainer.innerHTML = '';

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

                // Read stream chunks
                while (true) {
                    const {
                        value,
                        done
                    } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, {
                        stream: true
                    });

                    // Split by double newline which standard SSE uses to separate messages
                    const parts = buffer.split('\n\n');
                    buffer = parts.pop(); // Keep incomplete message in buffer

                    for (const part of parts) {
                        // Handle potential multiple lines within a single message block
                        // This handles cases where 'event: close' arrives in the same block as 'data: ...'
                        const lines = part.split('\n');

                        for (const line of lines) {
                            if (line.trim().startsWith('data: ')) {
                                try {
                                    const jsonStr = line.trim().substring(6);
                                    const data = JSON.parse(jsonStr);

                                    if (data.text) {
                                        // Append text chunk and update UI
                                        streamAccumulator += data.text;
                                        resBody.innerHTML = marked.parse(streamAccumulator);
                                        rawRes.value += data.text;
                                    } else if (data.error) {
                                        // CRITICAL FIX: Refresh token if server sent one with the error
                                        if (data.csrf_token) {
                                            this.app.refreshCsrf(data.csrf_token);
                                        }
                                        this.app.ui.injectFlashError(data.error);
                                    } else if (typeof data.cost !== 'undefined' && parseFloat(data.cost) > 0) {
                                        // Final Cost Packet: Show success flash message
                                        const costHtml = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="bi bi-check-circle-fill me-2"></i>
                                            KSH ${parseFloat(data.cost).toFixed(2)} deducted.
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>`;

                                        const flashContainer = document.getElementById('flash-messages-container');
                                        if (flashContainer) {
                                            flashContainer.innerHTML = costHtml;
                                        }

                                        // FIX: Handle Audio URL from final packet in Streaming
                                        if (data.audio_url) {
                                            if (audioContainer) {
                                                audioContainer.innerHTML = `
                                                    <div class="alert alert-info d-flex align-items-center mb-4">
                                                        <i class="bi bi-volume-up-fill fs-4 me-3"></i>
                                                        <audio controls autoplay class="w-100">
                                                            <source src="${data.audio_url}" type="audio/mpeg">
                                                        </audio>
                                                    </div>
                                                `;
                                            }
                                        }

                                    } else if (data.csrf_token) {
                                        // Update CSRF for subsequent requests
                                        this.app.refreshCsrf(data.csrf_token);
                                    }
                                } catch (e) {
                                    console.warn('JSON Parse Error in Stream:', e);
                                    this.app.ui.injectFlashError('JSON Parse Error in Stream: ' + e.message);
                                }
                            }
                        }
                    }
                }

                this.app.ui.setupCodeHighlighting();

            } catch (e) {
                console.error(e);
                this.app.ui.injectFlashError('Stream error occurred.');
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
                    // REFACTOR: Use Flash Message for Media Generation Errors
                    this.app.ui.injectFlashError(data.message);
                } else if (data.type === 'image') {
                    this.app.ui.showMediaResult(data.url, 'image');
                } else if (data.type === 'video') {
                    this.pollVideo(data.op_id);
                    return; // Don't reset loading yet
                }
            } catch (e) {
                console.error(e);
                this.app.ui.injectFlashError('Media generation failed.');
            }
            this.app.ui.setLoading(false);
        }

        pollVideo(opId) {
            this.app.ui.generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm text-white"></span>';
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
                        // REFACTOR: Use Flash Message for Polling Errors
                        this.app.ui.injectFlashError(res.message);
                        this.app.ui.setLoading(false);
                    }
                } catch (e) {}
            }, 5000);
        }

        handleMock(prompt, type) {
            const p = prompt.toLowerCase();
            if (p === '<p>test image</p>' && type === 'image') {
                setTimeout(() => {
                    this.app.ui.showMediaResult('https://picsum.photos/200/300?random=' + Date.now(), 'image');
                    this.app.ui.setLoading(false);
                }, 1000);
                return true;
            }
            if (p === '<p>test video</p>' && type === 'video') {
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