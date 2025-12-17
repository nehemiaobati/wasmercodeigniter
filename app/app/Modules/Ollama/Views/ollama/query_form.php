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

    /* Scoped Styles for Ollama View */
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
        border-top: 2px solid var(--bs-success);
        /* Green for Ollama */
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
        min-height: 40px;
        max-height: 120px;
        border-radius: 1.5rem;
        padding: 0.6rem 1rem;
        transition: border-color 0.2s;
        line-height: 1.5;
    }

    .prompt-textarea:focus {
        box-shadow: none;
        border-color: var(--bs-success);
        /* Green for Ollama */
    }

    /* Prompt Area Layout Adjustment */
    .ollama-prompt-area {
        padding: 1rem 1.5rem;
        padding-bottom: calc(1rem + env(safe-area-inset-bottom));
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
                <i class="bi bi-cpu text-success fs-4"></i>
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

            <!-- Flash Messages Container -->
            <div id="flash-messages-container">
                <?= view('App\Views\partials\flash_messages') ?>
            </div>

            <!-- Response -->
            <?php if ($result = session()->getFlashdata('result')): ?>
                <div class="card blueprint-card shadow-sm border-success" id="results-card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
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

                <!-- Uploads List -->
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
                        <!-- Model Selection Hidden Input -->
                        <input type="hidden" name="model" id="selectedModelInput" value="<?= $availableModels[0] ?? 'llama3' ?>">
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
                        <button type="submit" id="generateBtn" class="btn btn-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" title="Send">
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

        <hr>

        <!-- Toggles -->
        <div class="form-check form-switch mb-3">
            <input class="form-check-input setting-toggle" type="checkbox" id="assistantMode"
                data-key="assistant_mode_enabled" <?= $assistant_mode_enabled ? 'checked' : '' ?>>
            <label class="form-check-label fw-medium" for="assistantMode">Assistant Mode</label>
            <div class="form-text text-muted small lh-sm">
                Maintains context from previous messages.
            </div>
        </div>

        <!-- Stream Toggle -->
        <div class="form-check form-switch mb-3">
            <input class="form-check-input setting-toggle" type="checkbox" id="streamOutput"
                data-key="stream_output_enabled" <?= (isset($stream_output_enabled) && $stream_output_enabled) ? 'checked' : '' ?>>
            <label class="form-check-label fw-medium" for="streamOutput">Stream Response</label>
            <div class="form-text text-muted small lh-sm">
                Typewriter effect (Tokens appear as they are generated).
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
    <input type="hidden" name="content" id="dl_content">
    <input type="hidden" name="format" id="dl_format">
</form>

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
     * Refactored into modular classes for improved maintainability.
     */

    class OllamaApp {
        constructor() {
            this.config = {
                csrfName: '<?= csrf_token() ?>',
                csrfHash: document.querySelector('input[name="<?= csrf_token() ?>"]').value,
                maxFileSize: <?= $maxFileSize ?>,
                maxFiles: <?= $maxFiles ?>,
                supportedMimeTypes: <?= $supportedMimeTypes ?>,
                endpoints: {
                    upload: '<?= url_to('ollama.upload_media') ?>',
                    deleteMedia: '<?= url_to('ollama.delete_media') ?>',
                    settings: '<?= url_to('ollama.settings.update') ?>',
                    stream: '<?= url_to('ollama.stream') ?>',
                    download: '<?= url_to('ollama.download_document') ?>',
                    deletePromptBase: '<?= url_to('ollama.prompts.delete', 0) ?>'.slice(0, -1)
                }
            };

            // Modules
            this.ui = new UIManager(this);
            this.uploader = new MediaUploader(this);
            this.prompts = new PromptManager(this);
            this.interaction = new InteractionHandler(this);
        }

        init() {
            // Configure Marked.js
            if (typeof marked !== 'undefined') {
                marked.use({
                    breaks: true,
                    gfm: true
                });
            }

            this.ui.init();
            this.uploader.init();
            this.prompts.init();
            this.interaction.init();

            // Expose for global access
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

                // 1. Check Headers for CSRF (Primary Defense)
                const headerToken = res.headers.get('X-CSRF-TOKEN');
                if (headerToken) this.refreshCsrf(headerToken);

                // Parse JSON regardless of status to check for CSRF token in error responses
                const responseData = await res.json().catch(() => ({}));

                // 2. Check Body for CSRF (Secondary/Legacy)
                const bodyToken = responseData.token || responseData.csrf_token;
                if (bodyToken) this.refreshCsrf(bodyToken);

                if (!res.ok) {
                    throw new Error(responseData.message || 'Request failed');
                }

                return responseData;
            } catch (err) {
                console.error('AJAX Error:', err);
                this.ui.showToast(err.message || 'Network error occurred.');
                throw err;
            }
        }
    }

    class UIManager {
        constructor(app) {
            this.app = app;
        }

        init() {
            this.handleResponsiveSidebar();
            this.setupSettings();
            this.setupCodeHighlighting();
            this.setupAutoScroll();
            this.setupDownloads();
            this.initTinyMCE();
            this.setupModelSelector();
        }

        handleResponsiveSidebar() {
            if (window.innerWidth < 992) {
                const sidebar = document.getElementById('ollamaSidebar');
                if (sidebar && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
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
                placeholder: 'Message Ollama...',

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

        setupSettings() {
            document.querySelectorAll('.setting-toggle').forEach(toggle => {
                toggle.addEventListener('change', async (e) => {
                    const fd = new FormData();
                    fd.append('setting_key', e.target.dataset.key);
                    fd.append('enabled', e.target.checked);
                    try {
                        const data = await this.app.sendAjax(this.app.config.endpoints.settings, fd);
                        this.showToast(data.status === 'success' ? 'Setting saved.' : 'Failed to save.');
                    } catch (e) {
                        // Attempt to parse/refresh if error object has token (handled by sendAjax mostly but verify)
                        console.error(e);
                    }
                });
            });
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

        setupCodeHighlighting() {
            if (typeof hljs !== 'undefined') hljs.highlightAll();

            document.querySelectorAll('pre code').forEach((block) => {
                if (block.parentElement.querySelector('.copy-code-btn')) return;
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
                        fd.append('content', content);
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
                            const err = await response.json();
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

        ensureResultCardExists() {
            if (!document.getElementById('results-card')) {
                const wrapper = document.getElementById('response-area-wrapper');
                const emptyState = document.getElementById('empty-state');
                if (emptyState) emptyState.remove();

                const cardHtml = `
                <div class="card blueprint-card shadow-sm border-success" id="results-card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
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
                this.setupCodeHighlighting();
                this.setupDownloads();
            }
        }
    }

    class MediaUploader {
        constructor(app) {
            this.app = app;
            this.queue = [];
            this.isUploading = false;
        }

        clearUploads() {
            // Remove all chips
            const wrapper = document.getElementById('upload-list-wrapper');
            if (wrapper) wrapper.innerHTML = '';

            // Remove hidden inputs
            const container = document.getElementById('uploaded-files-container');
            if (container) container.innerHTML = '';

            // Reset queue
            this.queue = [];
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
                input.addEventListener('change', (e) => {
                    this.handleFiles(e.target.files);
                    input.value = ''; // Allow re-upload
                });

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

            Array.from(files).forEach(file => {
                // 1. Check file limit
                if (currentCount + accepted >= this.app.config.maxFiles) {
                    this.app.ui.showToast(`Max ${this.app.config.maxFiles} files allowed.`);
                    return;
                }

                // 2. Check MIME type
                if (!this.app.config.supportedMimeTypes.includes(file.type)) {
                    this.app.ui.showToast(`${file.name}: Unsupported file type`);
                    return;
                }

                // 3. Check file size
                if (file.size > this.app.config.maxFileSize) {
                    this.app.ui.showToast(`${file.name} exceeds ${(this.app.config.maxFileSize / 1024 / 1024).toFixed(1)}MB limit`);
                    return;
                }

                const id = Math.random().toString(36).substr(2, 9);
                const ui = this.createFileChip(file, id);
                this.queue.push({
                    file,
                    ui,
                    id
                });
                accepted++;
            });

            if (this.queue.length > 0) this.processQueue();
        }

        createFileChip(file, id) {
            const div = document.createElement('div');
            div.id = `file-item-${id}`;
            div.className = 'file-chip fade show';
            div.innerHTML = `
                <div class="progress-ring"></div>
                <span class="file-name" title="${file.name}">${file.name}</span>
                <button type="button" class="btn-close p-1 remove-btn disabled" style="width: 0.75rem; height: 0.75rem; opacity: 0.6;" data-id="${id}"></button>
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

            if (typeof tinymce !== 'undefined') tinymce.triggerSave();
            const prompt = document.getElementById('prompt').value.trim();

            if (!prompt) {
                this.app.ui.showToast('Please enter a prompt.');
                return;
            }

            this.app.ui.setLoading(true);
            const form = document.getElementById('ollamaForm');
            const fd = new FormData(form);

            // Check Stream Toggle
            const isStream = document.getElementById('streamOutput')?.checked;

            if (isStream) {
                await this.handleStream(fd);
            } else {
                await this.handleStandard(fd, form.action);
            }
        }

        async handleStandard(fd, action) {
            try {
                const data = await this.app.sendAjax(action, fd);

                if (data.status === 'success') {
                    // Update CSRF if present (sendAjax does it, but redundant check doesn't hurt)
                    if (data.csrf_token) this.app.refreshCsrf(data.csrf_token);

                    // 1. Ensure Result Card Exists
                    this.app.ui.ensureResultCardExists();

                    // 2. Update Content
                    document.getElementById('ai-response-body').innerHTML = data.result;
                    document.getElementById('raw-response').value = data.raw_result;

                    // 3. Highlight Code & Scroll
                    this.app.ui.setupCodeHighlighting();
                    this.app.ui.setupAutoScroll();

                    // 4. Update Flash Messages
                    if (data.flash_html) {
                        const flashContainer = document.getElementById('flash-messages-container');
                        if (flashContainer) flashContainer.innerHTML = data.flash_html;
                    }

                    // 5. Clear Uploads (Since they are processed)
                    this.app.uploader.clearUploads();
                } else {
                    this.app.ui.showToast(data.message || 'Generation failed.');
                }
            } catch (err) {
                console.error(err);
                this.app.ui.showToast('Error during generation.');
            } finally {
                this.app.ui.setLoading(false);
            }
        }

        async handleStream(fd) {
            try {
                // Ensure CSRF
                if (!fd.has(this.app.config.csrfName)) {
                    fd.append(this.app.config.csrfName, this.app.config.csrfHash);
                }

                const response = await fetch(this.app.config.endpoints.stream, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const headerToken = response.headers.get('X-CSRF-TOKEN');
                if (headerToken) this.app.refreshCsrf(headerToken);

                if (!response.ok) {
                    try {
                        const errRes = await response.json();
                        // Check body token in error response
                        if (errRes.csrf_token || errRes.token) {
                            this.app.refreshCsrf(errRes.csrf_token || errRes.token);
                        }
                        throw new Error(errRes.message || 'Stream failed');
                    } catch (e) {
                        if (e.message !== 'Stream failed') throw new Error('Network error'); // Re-throw if not ours
                        throw e;
                    }
                }

                this.app.ui.ensureResultCardExists();
                const resultBody = document.getElementById('ai-response-body');
                const rawResponse = document.getElementById('raw-response');

                // Clear previous if any
                resultBody.innerHTML = '';
                rawResponse.value = '';

                // Create a temporary content accumulator
                let fullText = '';
                let buffer = ''; // Buffer for split chunks
                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                while (true) {
                    const {
                        done,
                        value
                    } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, {
                        stream: true
                    });

                    // Split by double newline (SSE standard delimiter)
                    const parts = buffer.split('\n\n');
                    buffer = parts.pop(); // Keep incomplete message in buffer

                    for (const part of parts) {
                        const lines = part.split('\n');

                        for (const line of lines) {
                            if (line.trim().startsWith('data: ')) {
                                try {
                                    const payload = JSON.parse(line.trim().substring(6));

                                    if (payload.csrf_token) {
                                        this.app.refreshCsrf(payload.csrf_token);
                                    }

                                    if (payload.error) {
                                        this.app.ui.showToast(payload.error);
                                        return; // Stop
                                    }

                                    if (payload.text) {
                                        fullText += payload.text;
                                        // Use Marked if available, else raw text
                                        if (typeof marked !== 'undefined') {
                                            resultBody.innerHTML = marked.parse(fullText);
                                        } else {
                                            resultBody.innerText = fullText;
                                        }
                                    }

                                    if (payload.cost) {
                                        this.app.ui.showToast(`Initial Cost: ${payload.cost}`);
                                    }
                                } catch (e) {
                                    // Partial JSON ignore
                                }
                            }
                        }
                    }
                    // Auto scroll
                    this.app.ui.setupAutoScroll();
                }

                // Final Polish
                rawResponse.value = fullText;
                this.app.uploader.clearUploads();
                this.app.ui.setupCodeHighlighting();

            } catch (err) {
                console.error(err);
                this.app.ui.showToast('Stream failed.');
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