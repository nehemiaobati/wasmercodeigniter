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

    /* Scoped Styles for Ollama View (Mirrored from Gemini) */
    .ollama-view-container {
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
    .ollama-main {
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
    .ollama-response-area {
        flex: 1;
        overflow-y: auto;
        padding: 2rem;
        scroll-behavior: smooth;
        min-height: 0;
        /* Allow shrinking in flex container */
    }

    /* Sticky Header */
    .ollama-header {
        position: sticky;
        top: 0;
        z-index: 1020;
        background: var(--bs-body-bg);
    }

    /* Prompt Area (Sticky Bottom) */
    .ollama-prompt-area {
        width: 100%;
        background: var(--bs-body-bg);
        border-top: 1px solid var(--bs-border-color);
        padding: 1.5rem;
        z-index: 10;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
    }

    /* Settings Sidebar */
    .ollama-sidebar {
        width: 350px;
        border-left: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
        overflow-y: auto;
        height: 100%;
        padding: 1.5rem;
        transition: width 0.3s ease, padding 0.3s ease;
    }

    .ollama-sidebar.collapse:not(.show) {
        display: none;
    }

    /* Responsive Adjustments */
    @media (max-width: 991.98px) {
        .ollama-sidebar {
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

    /* Toast */
    .ollama-toast-container {
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
    .ollama-prompt-area {
        padding: 1rem 1.5rem;
        padding-bottom: calc(1rem + env(safe-area-inset-bottom));
        /* iOS Safe Area */
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="ollama-view-container">

    <!-- Main Content (Left/Center) -->
    <div class="ollama-main">
        <!-- Top Toolbar / Header -->
        <div class="d-flex justify-content-between align-items-center px-4 py-2 border-bottom bg-body ollama-header">
            <a href="<?= url_to('home') ?>" class="d-flex align-items-center gap-2 text-decoration-none text-reset">
                <i class="bi bi-cpu text-primary fs-4"></i>
                <span class="fw-bold fs-5">Local AI Studio (Ollama)</span>
            </a>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm theme-toggle" type="button" aria-label="Toggle theme">
                    <i class="bi bi-circle-half"></i>
                </button>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#ollamaSidebar" aria-expanded="true" aria-controls="ollamaSidebar">
                    <i class="bi bi-layout-sidebar-reverse"></i> Settings
                </button>
            </div>
        </div>

        <!-- Scrollable Response Area -->
        <div class="ollama-response-area" id="response-area-wrapper">

            <!-- Flash Messages Container for AJAX Injection -->
            <div id="flash-messages-container">
                <?= view('App\Views\partials\flash_messages') ?>
            </div>

            <!-- Audio Player Container (Dynamically Populated via AJAX - Kept for structure) -->
            <div id="audio-player-container"></div>

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
                    <p>Enter your prompt below to generate text with local models.</p>
                </div>
            <?php endif; ?>

        </div>

        <!-- Sticky Prompt Area -->
        <div class="ollama-prompt-area">
            <form id="ollamaForm" action="<?= url_to('ollama.generate') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>

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
                        <!-- Model Selection Hidden Input (Ollama Specific: Populated by Sidebar) -->
                        <input type="hidden" name="model" id="selectedModelInput" value="<?= $availableModels[0] ?? 'llama3' ?>">
                        <input type="hidden" name="generation_type" id="generationType" value="text"> <!-- Kept for JS compatibility -->

                        <textarea
                            id="prompt"
                            name="prompt"
                            class="form-control border-0 bg-transparent prompt-textarea shadow-none"
                            placeholder="Message Ollama..."
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
    <div class="ollama-sidebar collapse collapse-horizontal show" id="ollamaSidebar">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold m-0"><i class="bi bi-sliders"></i> Configuration</h5>
            <button type="button" class="btn-close d-lg-none" data-bs-toggle="collapse" data-bs-target="#ollamaSidebar"></button>
        </div>

        <!-- Model Selector (Ollama Specific) -->
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

        <hr>

        <!-- Toggles -->
        <div class="form-check form-switch mb-3">
            <input class="form-check-input setting-toggle" type="checkbox" id="assistantMode"
                data-key="assistant_mode_enabled" <?= $assistant_mode_enabled ? 'checked' : '' ?>>
            <label class="form-check-label fw-medium" for="assistantMode">Conversational Memory</label>
            <div class="form-text text-muted small lh-sm">
                Maintains context from previous messages.
            </div>
        </div>

        <div class="form-check form-switch mb-4">
            <input class="form-check-input setting-toggle" type="checkbox" id="streamOutput"
                data-key="stream_output_enabled" <?= (isset($stream_output_enabled) && $stream_output_enabled) ? 'checked' : '' ?>>
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
        <form action="<?= url_to('ollama.memory.clear') ?>" method="post" onsubmit="return confirm('Are you sure? This cannot be undone.');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                <i class="bi bi-trash me-2"></i> Clear History
            </button>
        </form>
    </div>
</div>

<!-- Hidden Forms/Modals -->
<form id="downloadForm" method="post" action="<?= url_to('ollama.download_document') ?>" target="_blank" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="raw_response" id="dl_raw"> <!-- Matched Gemini ID -->
    <input type="hidden" name="format" id="dl_format">
    <!-- Keep content for legacy if needed, but Gemini uses raw_response -->
    <input type="hidden" name="content" id="dl_content">
</form>

<!-- Hidden Delete Prompt Form -->
<form id="deletePromptForm" method="post" action="" class="d-none">
    <?= csrf_field() ?>
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

<div class="toast-container position-fixed bottom-0 p-3 ollama-toast-container">
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
     * Ollama Module - Frontend Application
     * Refactored into modular classes for improved maintainability and scalability.
     * STRICTLY MIRRORED FROM GEMINI APP to ensure CSRF stability.
     */

    class OllamaApp {
        constructor() {
            this.config = {
                csrfName: '<?= csrf_token() ?>',
                csrfHash: document.querySelector('input[name="<?= csrf_token() ?>"]').value,
                maxFileSize: <?= $maxFileSize ? $maxFileSize : 10 * 1024 * 1024 ?>, // Default fallback if not passed
                maxFiles: <?= $maxFiles ? $maxFiles : 5 ?>,
                supportedMimeTypes: <?= isset($supportedMimeTypes) ? $supportedMimeTypes : json_encode([]) ?>,
                endpoints: {
                    upload: '<?= url_to('ollama.upload_media') ?>',
                    deleteMedia: '<?= url_to('ollama.delete_media') ?>',
                    settings: '<?= url_to('ollama.settings.update') ?>',
                    deletePromptBase: '<?= url_to('ollama.prompts.delete', 0) ?>'.slice(0, -1),
                    stream: '<?= url_to('ollama.stream') ?>',
                    generate: '<?= url_to('ollama.generate') ?>',
                    download: '<?= url_to('ollama.download_document') ?>',
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
            window.ollamaApp = this;
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

                // 1. Check Payload for CSRF
                constnewToken = responseData.token || responseData.csrf_token;
                if (newToken) this.refreshCsrf(newToken);

                // 2. Check Headers for CSRF (Primary Defense)
                const headerToken = res.headers.get('X-CSRF-TOKEN');
                if (headerToken) this.refreshCsrf(headerToken);

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
            // setupTabs not needed for Ollama text-only view, but model selector needed
            this.handleResponsiveSidebar();
            this.setupModelSelector();
            this.setupSettings();
            this.setupCodeHighlighting();
            this.setupAutoScroll();
            this.setupDownloads();
            this.initTinyMCE();
        }

        handleResponsiveSidebar() {
            if (window.innerWidth < 992) {
                const sidebar = document.getElementById('ollamaSidebar');
                if (sidebar && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            }
        }

        setupModelSelector() {
            const selector = document.getElementById('modelSelector');
            const input = document.getElementById('selectedModelInput');
            if (selector && input) {
                selector.addEventListener('change', (e) => {
                    input.value = e.target.value;
                });
            }
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
                                document.getElementById('ollamaForm').requestSubmit();
                            }
                        }
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
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const rawDoc = document.getElementById('raw-response');
                    if (!rawDoc || !rawDoc.value) return;

                    const format = e.target.dataset.format;
                    const content = rawDoc.value;
                    const btnEl = e.target;

                    // Visual Feedback
                    const originalText = btnEl.textContent;
                    btnEl.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Downloading...';
                    btnEl.closest('.dropdown').querySelector('.dropdown-toggle').disabled = true;

                    try {
                        const fd = new FormData();
                        fd.append(this.app.config.csrfName, this.app.config.csrfHash);
                        // Handle legacy "content" vs new "raw_response" input expectation
                        fd.append('content', content);
                        fd.append('raw_response', content);
                        fd.append('format', format);

                        const response = await fetch(this.app.config.endpoints.download, {
                            method: 'POST',
                            body: fd,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        // Check for CSRF Header
                        const headerToken = response.headers.get('X-CSRF-TOKEN');
                        if (headerToken) this.app.refreshCsrf(headerToken);

                        if (!response.ok) {
                            const err = await response.json().catch(() => ({}));
                            throw new Error(err.message || 'Download failed');
                        }

                        // Handle Blob
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        // Get filename from header if possible, else default
                        const disposition = response.headers.get('Content-Disposition');
                        let filename = `ollama-export.${format}`;
                        if (disposition && disposition.indexOf('attachment') !== -1) {
                            const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                            const matches = filenameRegex.exec(disposition);
                            if (matches != null && matches[1]) {
                                filename = matches[1].replace(/['"]/g, '');
                            }
                        }
                        a.download = filename;

                        document.body.appendChild(a);
                        a.click();

                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        this.showToast('Download started');

                    } catch (err) {
                        console.error(err);
                        this.showToast(err.message);
                    } finally {
                        btnEl.textContent = originalText;
                        btnEl.closest('.dropdown').querySelector('.dropdown-toggle').disabled = false;
                    }
                });
            });
            const copyFull = document.getElementById('copyFullResponseBtn');
            if (copyFull) {
                copyFull.addEventListener('click', () => {
                    const rawDoc = document.getElementById('raw-response');
                    if (rawDoc) {
                        navigator.clipboard.writeText(rawDoc.value)
                            .then(() => this.showToast('Copied!'));
                    }
                });
            }
        }

        setLoading(isLoading) {
            const btn = document.getElementById('generateBtn');
            if (isLoading) {
                btn.disabled = true;
                btn.innerHTML = `<span class="spinner-border spinner-border-sm text-white" role="status" aria-hidden="true"></span>`;
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-up text-white fs-5"></i>';
            }
        }

        // Ensure result card exists (Strict Mirror)
        ensureResultCardExists() {
            if (!document.getElementById('results-card')) {
                const wrapper = document.getElementById('response-area-wrapper');
                const emptyState = document.getElementById('empty-state');
                if (emptyState) emptyState.remove();

                const cardHtml = `
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
                    <div class="card-body response-content" id="ai-response-body"></div>
                    <textarea id="raw-response" class="d-none"></textarea>
                    <div class="card-footer bg-transparent border-0 text-center">
                        <small class="text-muted fst-italic"><i class="bi bi-info-circle me-1"></i> AI can make mistakes. Please verify important information.</small>
                    </div>
                </div>`;
                wrapper.insertAdjacentHTML('beforeend', cardHtml);

                // Re-bind events for the new card
                this.setupCodeHighlighting();
                this.setupDownloads(); // Bind new export buttons
            }
        }
    }

    class MediaUploader {
        constructor(app) {
            this.app = app;
            this.input = document.getElementById('media-input-trigger');
            this.dropZone = document.getElementById('mediaUploadArea');
            this.listContainer = document.getElementById('upload-list-wrapper'); // Chips container
            this.uploadedContainer = document.getElementById('uploaded-files-container'); // Hidden inputs
        }

        init() {
            if (!this.input || !this.dropZone) return;

            // Click Handler
            this.input.addEventListener('change', (e) => this.handleFiles(e.target.files));

            // Drag & Drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                this.dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            this.dropZone.addEventListener('dragover', () => this.dropZone.classList.add('dragover'));
            this.dropZone.addEventListener('dragleave', () => this.dropZone.classList.remove('dragover'));
            this.dropZone.addEventListener('drop', (e) => {
                this.dropZone.classList.remove('dragover');
                this.handleFiles(e.dataTransfer.files);
            });
        }

        async handleFiles(files) {
            const maxFiles = this.app.config.maxFiles;
            const currentFiles = this.uploadedContainer.querySelectorAll('input[name="uploaded_files[]"]').length;

            if (currentFiles + files.length > maxFiles) {
                this.app.ui.showToast(`Max ${maxFiles} files allowed.`);
                return;
            }

            for (const file of files) {
                if (!this.validateFile(file)) continue;
                await this.uploadFile(file);
            }
            this.input.value = ''; // Reset input
        }

        validateFile(file) {
            const types = this.app.config.supportedMimeTypes;
            if (!types.includes(file.type)) {
                this.app.ui.showToast(`FileType ${file.type} not supported.`);
                return false;
            }
            if (file.size > this.app.config.maxFileSize) {
                this.app.ui.showToast(`File ${file.name} too large.`);
                return false;
            }
            return true;
        }

        async uploadFile(file) {
            // Create Chip (Optimistic UI)
            const id = 'file-' + Date.now() + Math.random().toString(36).substr(2, 9);
            const chip = document.createElement('div');
            chip.className = 'file-chip';
            chip.id = id;
            chip.innerHTML = `
                <div class="progress-ring"></div>
                <span class="file-name" title="${file.name}">${file.name}</span>
                <button type="button" class="btn-close btn-close-white small remove-btn disabled" aria-label="Remove"></button>
            `;
            this.listContainer.appendChild(chip);

            const formData = new FormData();
            formData.append('file', file);
            formData.append(this.app.config.csrfName, this.app.config.csrfHash);

            try {
                const data = await this.app.sendAjax(this.app.config.endpoints.upload, formData);
                if (data.status === 'success') {
                    // Update Chip
                    chip.querySelector('.progress-ring').remove();
                    const icon = document.createElement('i');
                    icon.className = 'bi bi-file-earmark-check text-success me-2';
                    chip.prepend(icon);

                    const removeBtn = chip.querySelector('.remove-btn');
                    removeBtn.classList.remove('disabled');
                    removeBtn.onclick = () => this.removeFile(id, data.file_path, data.file_name); // Assuming backend returns stored name/path

                    // Add hidden input
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'uploaded_files[]';
                    input.value = data.file_name; // Or ID, depends on backend
                    input.id = `input-${id}`;
                    this.uploadedContainer.appendChild(input);

                } else {
                    chip.remove();
                    this.app.ui.showToast(data.message || 'Upload failed');
                }
            } catch (e) {
                chip.remove();
                this.app.ui.showToast('Upload error');
            }
        }

        async removeFile(chipId, filePath, fileName) {
            const chip = document.getElementById(chipId);
            if (!chip) return;

            // Optimistic removal
            chip.remove();
            const input = document.getElementById(`input-${chipId}`);
            if (input) input.remove();

            const fd = new FormData();
            fd.append('file_path', filePath); // Adjust based on Controller needs
            fd.append('file_name', fileName);

            try {
                await this.app.sendAjax(this.app.config.endpoints.deleteMedia, fd);
            } catch (e) {
                console.error('Delete failed', e);
            }
        }

        clearUploads() {
            this.listContainer.innerHTML = '';
            this.uploadedContainer.innerHTML = '';
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
                    if (typeof tinymce !== 'undefined' && tinymce.get('prompt')) {
                        tinymce.get('prompt').setContent(select.value);
                    } else {
                        document.getElementById('prompt').value = select.value;
                    }
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

            try {
                const data = await this.app.sendAjax(formData.get('action') || modalEl.querySelector('form').action, formData);

                if (data.status === 'success') {
                    this.app.ui.showToast('Saved!');
                    modal.hide();

                    if (data.prompt && data.prompt.id) {
                        const select = document.getElementById('savedPrompts');
                        const option = document.createElement('option');
                        option.value = data.prompt.prompt_text;
                        option.textContent = data.prompt.title;
                        option.dataset.id = data.prompt.id;
                        select.appendChild(option);
                        select.value = data.prompt.prompt_text;

                        const deleteBtn = document.getElementById('deletePromptBtn');
                        if (deleteBtn) deleteBtn.disabled = false;
                    }
                } else {
                    this.app.ui.showToast(data.message || 'Failed');
                }
            } catch (e) {
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
            const form = document.getElementById('ollamaForm');
            if (form) form.addEventListener('submit', (e) => this.handleSubmit(e));
        }

        async handleSubmit(e) {
            e.preventDefault();
            // Ollama only supports text generation in this view
            const useStreaming = document.getElementById('streamOutput')?.checked;

            if (typeof tinymce !== 'undefined') tinymce.triggerSave();
            const prompt = document.getElementById('prompt').value.trim();
            if (!prompt) {
                this.app.ui.showToast('Please enter a prompt.');
                return;
            }

            this.app.ui.setLoading(true);
            const fd = new FormData(document.getElementById('ollamaForm'));

            // Text Generation: Determine flow based on Streaming setting
            if (useStreaming) {
                await this.handleStreaming(fd);
            } else {
                await this.handleStandardGeneration(fd);
            }
        }

        /**
         * Handles standard (non-streaming) text generation via Fetch.
         * Updates content and injects flash messages via AJAX.
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

                    // Clear Uploads (Since they are processed)
                    this.app.uploader.clearUploads();

                } else if (data.status === 'error') {
                    this.app.ui.injectFlashError(data.message || 'Generation failed.');
                }

            } catch (e) {
                console.error(e);
                this.app.ui.injectFlashError('Generation failed due to a system error.');
            } finally {
                this.app.ui.setLoading(false);
            }
        }

        /**
         * CRITICAL: STRICT MIRROR OF GEMINI handleStreaming
         * Handles SSE streaming with robust CSRF and TextDecoder support
         */
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
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
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
                        const lines = part.split('\n');

                        for (const line of lines) {
                            if (line.trim().startsWith('data: ')) {
                                try {
                                    const jsonStr = line.trim().substring(6);
                                    const data = JSON.parse(jsonStr);

                                    if (data.text) {
                                        // Append text chunk and update UI
                                        streamAccumulator += data.text;
                                        if (typeof marked !== 'undefined') {
                                            resBody.innerHTML = marked.parse(streamAccumulator);
                                        } else {
                                            resBody.innerText = streamAccumulator;
                                        }
                                        rawRes.value += data.text;
                                    } else if (data.error) {
                                        // CRITICAL FIX: Refresh token if server sent one with the error
                                        if (data.csrf_token) {
                                            this.app.refreshCsrf(data.csrf_token);
                                        }
                                        this.app.ui.injectFlashError(data.error);
                                    } else if (typeof data.cost !== 'undefined') {
                                        // Final Cost Packet: Show success flash message
                                        // Note: Ollama might send cost: 0 or similar, but structure remains
                                        if (parseFloat(data.cost) > 0) {
                                            const costHtml = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                                                <i class="bi bi-check-circle-fill me-2"></i>
                                                KSH ${parseFloat(data.cost).toFixed(2)} deducted.
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>`;
                                            const flashContainer = document.getElementById('flash-messages-container');
                                            if (flashContainer) flashContainer.innerHTML = costHtml;
                                        }

                                        // Handle Audio URL from final packet (Structure Copy, even if Ollama does not use it yet)
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
                                        // Update CSRF for subsequent requests (e.g. from KeepAlive or intermediate events)
                                        this.app.refreshCsrf(data.csrf_token);
                                    }
                                } catch (e) {
                                    console.warn('JSON Parse Error in Stream:', e);
                                    // this.app.ui.injectFlashError('JSON Parse Error in Stream: ' + e.message); // Optional: suppress noisy parse errors
                                }
                            }
                        }
                    }
                    // Auto scroll
                    this.app.ui.setupAutoScroll();
                }

                // Final Polish
                // Ensure final raw value matches accumulator, mirroring handleStandard
                rawRes.value = streamAccumulator;

                this.app.ui.setupCodeHighlighting();
                this.app.uploader.clearUploads();

            } catch (e) {
                console.error(e);
                this.app.ui.injectFlashError('Stream error occurred.');
            } finally {
                this.app.ui.setLoading(false);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        new OllamaApp().init();
    });
</script>
<?= $this->endSection() ?>