<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<!-- External Styles -->
<link rel="stylesheet" href="<?= base_url('public/assets/highlight/styles/atom-one-dark.min.css') ?>">

<style>
    /* 
    |--------------------------------------------------------------------------
    | AI Studio Implementation - Internal Styles (Ollama)
    |--------------------------------------------------------------------------
    | Scoped styles for the AI Studio interface.
    */

    /* --- Global Layout Overrides --- */
    #mainNavbar,
    .footer,
    .container.my-4 {
        display: none !important;
    }

    body {
        overflow: hidden;
        padding: 0 !important;
        background-color: var(--bs-body-bg);
    }

    /* --- Main Container & Layout --- */
    .ollama-view-container {
        --code-bg: #282c34;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        height: 100dvh;
        width: 100vw;
        display: flex;
        overflow: hidden;
        z-index: 1000;
        background-color: var(--bs-body-bg);
    }

    .ollama-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
        min-width: 0;
        overflow: hidden;
    }

    /* --- Header --- */
    .ollama-header {
        position: sticky;
        top: 0;
        z-index: 1020;
        background: var(--bs-body-bg);
        border-bottom: 1px solid var(--bs-border-color);
        padding: 0.5rem 1.5rem;
    }

    /* --- Response Area --- */
    .ollama-response-area {
        flex: 1;
        overflow-y: auto;
        padding: 2rem;
        scroll-behavior: smooth;
        min-height: 0;
    }

    /* --- Prompt Area --- */
    .ollama-prompt-area {
        width: 100%;
        background: var(--bs-body-bg);
        border-top: 1px solid var(--bs-border-color);
        padding: 1rem 1.5rem calc(1rem + env(safe-area-inset-bottom));
        z-index: 10;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
    }

    .prompt-textarea {
        resize: none;
        overflow-y: hidden;
        min-height: 40px;
        max-height: 120px;
        border-radius: 1.5rem;
        padding: 0.6rem 1rem;
        line-height: 1.5;
        transition: border-color 0.2s;
    }

    .prompt-textarea:focus {
        box-shadow: none;
        border-color: var(--bs-primary);
    }

    /* --- Sidebar --- */
    .ollama-sidebar {
        width: 350px;
        border-left: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
        overflow-y: auto;
        height: 100%;
        padding: 1.5rem;
        transition: 0.3s ease;
    }

    .ollama-sidebar.collapse:not(.show) {
        display: none;
    }

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

    /* Upload List */
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

    /* --- Code Blocks & Copy --- */
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
        top: 8px;
        right: 8px;
        opacity: 0;
        transition: all 0.2s ease;
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(5px);
        background: rgba(0, 0, 0, 0.2) !important;
        font-size: 0.85rem;
        padding: 0.25rem 0.5rem;
        z-index: 5;
    }

    pre:hover .copy-code-btn {
        opacity: 1;
    }

    .copy-code-btn:hover {
        background: rgba(0, 0, 0, 0.4) !important;
        border-color: rgba(255, 255, 255, 0.5);
        transform: translateY(-1px);
    }

    .copy-code-btn.copied {
        background: rgba(40, 167, 69, 0.8) !important;
        border-color: rgba(40, 167, 69, 1);
    }

    /* Results Card */
    #results-card {
        overflow: visible;
        border-radius: var(--bs-border-radius);
    }

    #results-card .card-header {
        border-top-left-radius: calc(var(--bs-border-radius) - 1px);
        border-top-right-radius: calc(var(--bs-border-radius) - 1px);
    }

    #results-card .card-footer {
        border-bottom-left-radius: calc(var(--bs-border-radius) - 1px);
        border-bottom-right-radius: calc(var(--bs-border-radius) - 1px);
    }

    /* Drag & Drop */
    #mediaUploadArea {
        border-radius: 0.5rem;
        transition: 0.2s;
    }

    #mediaUploadArea.dragover {
        background: var(--bs-primary-bg-subtle);
        outline: 1px dashed var(--bs-primary);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="ollama-view-container">

    <!-- Main Content -->
    <div class="ollama-main">
        <!-- Header -->
        <div class="ollama-header d-flex justify-content-between align-items-center">
            <a href="<?= url_to('home') ?>" class="d-flex align-items-center gap-2 text-decoration-none text-reset">
                <i class="bi bi-cpu text-primary fs-4"></i>
                <span class="fw-bold fs-5">Local AI Studio</span>
            </a>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm theme-toggle" title="Toggle Theme"><i class="bi bi-circle-half"></i></button>
                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#ollamaSidebar">
                    <i class="bi bi-layout-sidebar-reverse"></i> Settings
                </button>
            </div>
        </div>

        <!-- Response Area -->
        <div class="ollama-response-area" id="response-area-wrapper">
            <div id="flash-messages-container"><?= view('App\Views\partials\flash_messages') ?></div>

            <?php if ($result = session()->getFlashdata('result')): ?>
                <!-- Server-Side Rendered Result -->
                <div class="card blueprint-card shadow-sm border-primary" id="results-card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="bi bi-stars me-2"></i>Studio Output</span>
                        <div class="d-flex gap-2">
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-light copy-btn" id="copyFullResponseBtn" data-format="text"><i class="bi bi-clipboard me-1"></i> Copy</button>
                                <button type="button" class="btn btn-sm btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"><span class="visually-hidden">Toggle</span></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <h6 class="dropdown-header"><i class="bi bi-clipboard me-1"></i> Copy As</h6>
                                    </li>
                                    <li><a class="dropdown-item copy-format-action" href="#" data-format="text"><i class="bi bi-file-text me-2"></i> Plain Text</a></li>
                                    <li><a class="dropdown-item copy-format-action" href="#" data-format="markdown"><i class="bi bi-markdown me-2"></i> Markdown</a></li>
                                    <li><a class="dropdown-item copy-format-action" href="#" data-format="html"><i class="bi bi-code-square me-2"></i> HTML</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <h6 class="dropdown-header"><i class="bi bi-download me-1"></i> Export As</h6>
                                    </li>
                                    <li><a class="dropdown-item download-action" href="#" data-format="pdf"><i class="bi bi-file-pdf me-2"></i> PDF Document</a></li>
                                    <li><a class="dropdown-item download-action" href="#" data-format="docx"><i class="bi bi-file-word me-2"></i> Word Document</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body response-content" id="ai-response-body"><?= $result ?></div>
                    <textarea id="raw-response" class="d-none"><?= esc(session()->getFlashdata('raw_result')) ?></textarea>
                    <div class="card-footer bg-body border-top text-center py-2">
                        <small class="text-muted fw-medium d-block">Generated by Ollama Local Models</small>
                        <small class="text-muted" style="font-size: 0.7rem;">AI may make mistakes. Verify important information.</small>
                    </div>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center text-muted mt-5 pt-5" id="empty-state">
                    <div class="display-1 text-body-tertiary mb-3"><i class="bi bi-lightbulb"></i></div>
                    <h5>Start Creating</h5>
                    <p>Enter your prompt to generate response with local Llama models.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Prompt Area -->
        <div class="ollama-prompt-area">
            <form id="ollamaForm" action="<?= url_to('ollama.generate') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div id="upload-list-wrapper"></div>
                <div id="uploaded-files-container"></div>

                <div class="d-flex align-items-end gap-2 bg-body-tertiary p-2 rounded-4 border">
                    <!-- Media Upload Trigger -->
                    <div id="mediaUploadArea" class="d-inline-block p-0 border-0 bg-transparent mb-1">
                        <input type="file" id="media-input-trigger" multiple class="d-none">
                        <label for="media-input-trigger" class="btn btn-link text-secondary p-1" title="Attach files">
                            <i class="bi bi-paperclip fs-4"></i>
                        </label>
                    </div>

                    <!-- Input Fields -->
                    <div class="flex-grow-1">
                        <input type="hidden" name="model" id="selectedModelInput" value="<?= $availableModels[0] ?? 'llama3' ?>">
                        <input type="hidden" name="generation_type" value="text">
                        <textarea id="prompt" name="prompt" class="form-control border-0 bg-transparent prompt-textarea shadow-none" placeholder="Message Ollama..." rows="1"><?= old('prompt') ?></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <button type="button" class="btn btn-link text-secondary p-1" data-bs-toggle="modal" data-bs-target="#savePromptModal" title="Save Prompt"><i class="bi bi-bookmark-plus fs-5"></i></button>
                        <button type="submit" id="generateBtn" class="btn btn-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" title="Generate"><i class="bi bi-arrow-up text-white fs-5"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Sidebar (Settings) -->
    <div class="ollama-sidebar collapse collapse-horizontal show" id="ollamaSidebar">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold m-0"><i class="bi bi-sliders"></i> Configuration</h5>
            <button class="btn-close d-lg-none" data-bs-toggle="collapse" data-bs-target="#ollamaSidebar"></button>
        </div>

        <!-- Model Selection -->
        <div class="mb-4">
            <label class="form-label small fw-bold text-uppercase text-muted">Model</label>
            <select class="form-select" id="modelSelector">
                <?php foreach ($availableModels as $model): ?>
                    <option value="<?= esc($model) ?>"><?= esc($model) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input setting-toggle" type="checkbox" id="assistantMode" data-key="assistant_mode_enabled" <?= $assistant_mode_enabled ? 'checked' : '' ?>>
            <label class="form-check-label fw-medium" for="assistantMode">Conversational Memory</label>
        </div>
        <div class="form-check form-switch mb-4">
            <input class="form-check-input setting-toggle" type="checkbox" id="streamOutput" data-key="stream_output_enabled" <?= (isset($stream_output_enabled) && $stream_output_enabled) ? 'checked' : '' ?>>
            <label class="form-check-label fw-medium" for="streamOutput">Stream Responses</label>
        </div>
        <hr>

        <label class="form-label small fw-bold text-uppercase text-muted">Saved Prompts</label>
        <div id="saved-prompts-wrapper">
            <div class="input-group mb-3 <?= empty($prompts) ? 'd-none' : '' ?>" id="savedPromptsContainer">
                <select class="form-select form-select-sm" id="savedPrompts">
                    <option value="" disabled selected>Select...</option>
                    <?php if (!empty($prompts)): ?>
                        <?php foreach ($prompts as $p): ?><option value="<?= esc($p->prompt_text, 'attr') ?>" data-id="<?= $p->id ?>"><?= esc($p->title) ?></option><?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <button class="btn btn-outline-secondary btn-sm" type="button" id="usePromptBtn">Load</button>
                <button class="btn btn-outline-danger btn-sm" type="button" id="deletePromptBtn" disabled><i class="bi bi-trash"></i></button>
            </div>
            <div id="no-prompts-alert" class="alert alert-light border mb-3 small text-muted <?= !empty($prompts) ? 'd-none' : '' ?>">
                No saved prompts yet.
            </div>
        </div>

        <hr>
        <form action="<?= url_to('ollama.memory.clear') ?>" method="post" onsubmit="return confirm('Clear all history?');">
            <?= csrf_field() ?><button type="submit" class="btn btn-outline-danger w-100 btn-sm"><i class="bi bi-trash me-2"></i> Clear History</button>
        </form>

        <div class="mt-auto pt-4 text-center">
            <small class="text-muted">AFRIKENKID AI Studio v2</small>
        </div>
    </div>
</div>

<!-- Hidden Forms -->
<form id="downloadForm" method="post" action="<?= url_to('ollama.download_document') ?>" target="_blank" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="raw_response" id="dl_raw"><input type="hidden" name="format" id="dl_format">
</form>

<!-- Modal -->
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

<!-- Toast -->
<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3 ollama-toast-container">
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
     * ==========================================
     * Ollama AI Studio - Frontend Application
     * ==========================================
     */

    const APP_CONFIG = {
        csrfName: '<?= csrf_token() ?>',
        csrfHash: document.querySelector('input[name="<?= csrf_token() ?>"]').value,
        limits: {
            maxFileSize: <?= $maxFileSize ?? 10 * 1024 * 1024 ?>,
            maxFiles: <?= $maxFiles ?? 5 ?>,
            supportedTypes: <?= $supportedMimeTypes ?? '[]' ?>,
        },
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

    /**
     * 1. Core Application Controller
     */
    class OllamaApp {
        constructor() {
            this.csrfHash = APP_CONFIG.csrfHash;

            this.ui = new UIManager(this);
            this.uploader = new MediaUploader(this);
            this.prompts = new PromptManager(this);
            this.streamer = new StreamHandler(this);
            this.interaction = new InteractionHandler(this);
        }

        init() {
            if (typeof marked !== 'undefined') marked.use({
                breaks: true,
                gfm: true
            });

            this.ui.init();
            this.uploader.init();
            this.prompts.init();
            this.interaction.init();

            window.ollamaApp = this;
        }

        refreshCsrf(hash) {
            if (!hash) return;
            this.csrfHash = hash;
            document.querySelectorAll(`input[name="${APP_CONFIG.csrfName}"]`).forEach(el => el.value = hash);
        }

        async sendAjax(url, data = null) {
            const formData = data instanceof FormData ? data : new FormData();
            if (!formData.has(APP_CONFIG.csrfName)) formData.append(APP_CONFIG.csrfName, this.csrfHash);

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
                const json = await res.json();

                const token = json.token || json.csrf_token || res.headers.get('X-CSRF-TOKEN');
                if (token) this.refreshCsrf(token);

                return json;
            } catch (e) {
                console.error("AJAX Failure", e);
                this.ui.showToast('Communication error.');
                throw e;
            }
        }
    }

    /**
     * 2. UI Manager
     */
    class UIManager {
        constructor(app) {
            this.app = app;
            this.generateBtn = document.getElementById('generateBtn');
        }

        init() {
            this.handleResponsiveSidebar();
            this.setupModelSelector();
            this.setupSettings();
            this.setupCodeHighlighting();
            this.setupAutoScroll();
            this.setupDownloads();
            this.initTinyMCE();
            this.setupAutoResize();
        }

        handleResponsiveSidebar() {
            if (window.innerWidth < 992) {
                const sb = document.getElementById('ollamaSidebar');
                if (sb && sb.classList.contains('show')) sb.classList.remove('show');
            }
        }

        setupModelSelector() {
            const sel = document.getElementById('modelSelector');
            const inp = document.getElementById('selectedModelInput');
            if (sel && inp) sel.addEventListener('change', () => inp.value = sel.value);
        }

        setupAutoResize() {
            const textarea = document.getElementById('prompt');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
        }

        initTinyMCE() {
            // TinyMCE setup for rich text input if used
            if (typeof tinymce === 'undefined') return;
            tinymce.init({
                selector: '#prompt',
                menubar: false,
                statusbar: false,
                toolbar: false,
                license_key: 'gpl',
                plugins: 'autoresize',
                autoresize_bottom_margin: 0,
                min_height: 40,
                max_height: 120,
                highlight_on_focus: false, // Prevents TinyMCE's specific focus highlighting
                content_style: 'body { outline: none !important; }',
                setup: (editor) => {
                    editor.on('keydown', (e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            if (editor.getContent().trim()) {
                                editor.save();
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

        injectFlashError(msg) {
            const c = document.getElementById('flash-messages-container');
            if (c) c.innerHTML = `<div class="alert alert-danger alert-dismissible fade show">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
        }

        setupSettings() {
            document.querySelectorAll('.setting-toggle').forEach(t => {
                t.addEventListener('change', async (e) => {
                    const fd = new FormData();
                    fd.append('setting_key', e.target.dataset.key);
                    fd.append('enabled', e.target.checked);
                    try {
                        const d = await this.app.sendAjax(APP_CONFIG.endpoints.settings, fd);
                        if (d.status !== 'success') this.showToast('Failed to save setting.');
                    } catch (e) {}
                });
            });
        }

        setupCodeHighlighting() {
            if (typeof hljs !== 'undefined') hljs.highlightAll();

            document.querySelectorAll('pre code').forEach((b) => {
                if (b.parentElement.querySelector('.copy-code-btn')) return;
                const btn = document.createElement('button');
                btn.className = 'btn btn-sm btn-dark copy-code-btn';
                btn.innerHTML = '<i class="bi bi-clipboard"></i>';
                btn.onclick = (e) => {
                    e.preventDefault();
                    navigator.clipboard.writeText(b.innerText).then(() => {
                        btn.classList.add('copied');
                        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                        setTimeout(() => {
                            btn.innerHTML = '<i class="bi bi-clipboard"></i>';
                            btn.classList.remove('copied');
                        }, 2000);
                    });
                };
                b.parentElement.appendChild(btn);
            });
        }

        ensureResultCardExists() {
            const existing = document.getElementById('results-card');
            if (existing) return;
            document.getElementById('empty-state')?.remove();

            const html = `
                <div class="card blueprint-card shadow-sm border-primary" id="results-card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="bi bi-stars me-2"></i>Studio Output</span>
                         <div class="d-flex gap-2">
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-light copy-btn" id="copyFullResponseBtn" data-format="text"><i class="bi bi-clipboard me-1"></i> Copy</button>
                                <button type="button" class="btn btn-sm btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"><span class="visually-hidden">Toggle</span></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><h6 class="dropdown-header"><i class="bi bi-clipboard me-1"></i> Copy As</h6></li>
                                    <li><a class="dropdown-item copy-format-action" href="#" data-format="text"><i class="bi bi-file-text me-2"></i> Plain Text</a></li>
                                    <li><a class="dropdown-item copy-format-action" href="#" data-format="markdown"><i class="bi bi-markdown me-2"></i> Markdown</a></li>
                                    <li><a class="dropdown-item copy-format-action" href="#" data-format="html"><i class="bi bi-code-square me-2"></i> HTML</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header"><i class="bi bi-download me-1"></i> Export As</h6></li>
                                    <li><a class="dropdown-item download-action" href="#" data-format="pdf"><i class="bi bi-file-pdf me-2"></i> PDF Document</a></li>
                                    <li><a class="dropdown-item download-action" href="#" data-format="docx"><i class="bi bi-file-word me-2"></i> Word Document</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body response-content" id="ai-response-body"></div>
                    <textarea id="raw-response" class="d-none"></textarea>
                    <div class="card-footer bg-body border-top text-center py-2">
                        <small class="text-muted fw-medium d-block">Generated by Ollama Local Models</small>
                        <small class="text-muted" style="font-size: 0.7rem;">AI may make mistakes. Verify important information.</small>
                    </div>
                </div>`;
            document.getElementById('response-area-wrapper').insertAdjacentHTML('beforeend', html);
            this.setupDownloads();
        }

        setupAutoScroll() {
            setTimeout(() => document.getElementById('results-card')?.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            }), 100);
        }

        setupDownloads() {
            document.querySelectorAll('.download-action').forEach(btn => {
                btn.onclick = (e) => {
                    e.preventDefault();
                    document.getElementById('dl_raw').value = document.getElementById('raw-response').value;
                    document.getElementById('dl_format').value = e.target.dataset.format;
                    document.getElementById('downloadForm').submit();
                };
            });
            const mainCopyBtn = document.getElementById('copyFullResponseBtn');
            if (mainCopyBtn) mainCopyBtn.onclick = () => this.copyContent('text', mainCopyBtn);

            document.querySelectorAll('.copy-format-action').forEach(btn => {
                btn.onclick = (e) => {
                    e.preventDefault();
                    this.copyContent(e.target.dataset.format, mainCopyBtn);
                };
            });
        }

        copyContent(format, btn) {
            const raw = document.getElementById('raw-response');
            const body = document.getElementById('ai-response-body');
            if (!raw || !body) return;

            let content = '';
            switch (format) {
                case 'markdown':
                    content = raw.value;
                    break;
                case 'html':
                    content = body.innerHTML;
                    break;
                default:
                    content = body.innerText;
            }
            if (!content.trim()) {
                this.showToast('Nothing to copy.');
                return;
            }

            navigator.clipboard.writeText(content).then(() => {
                this.showToast('Copied!');
                if (btn) {
                    const original = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied';
                    setTimeout(() => btn.innerHTML = original, 2000);
                }
            });
        }

        setLoading(loading) {
            if (loading) {
                this.generateBtn.disabled = true;
                this.generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm text-white"></span>';
            } else {
                this.generateBtn.disabled = false;
                this.generateBtn.innerHTML = '<i class="bi bi-arrow-up text-white fs-5"></i>';
            }
        }
    }

    /**
     * 3. Media Uploader
     */
    class MediaUploader {
        constructor(app) {
            this.app = app;
            this.files = new Map();
        }

        init() {
            const input = document.getElementById('media-input-trigger');
            const area = document.getElementById('mediaUploadArea');
            if (!input || !area) return;

            input.onchange = (e) => this.handleFiles(e.target.files);

            // Drag and drop
            area.ondragover = (e) => {
                e.preventDefault();
                area.classList.add('dragover');
            };
            area.ondragleave = () => area.classList.remove('dragover');
            area.ondrop = (e) => {
                e.preventDefault();
                area.classList.remove('dragover');
                this.handleFiles(e.dataTransfer.files);
            };
        }

        async handleFiles(fileList) {
            if (this.files.size + fileList.length > APP_CONFIG.limits.maxFiles) {
                this.app.ui.showToast(`Max ${APP_CONFIG.limits.maxFiles} files allowed.`);
                return;
            }

            Array.from(fileList).forEach(file => {
                if (file.size > APP_CONFIG.limits.maxFileSize) {
                    this.app.ui.showToast(`File ${file.name} too large.`);
                    return;
                }
                this.uploadFile(file);
            });
        }

        async uploadFile(file) {
            const id = Math.random().toString(36).substr(2, 9);
            this.renderFileChip(id, file.name);

            const fd = new FormData();
            fd.append('file', file);

            try {
                const res = await this.app.sendAjax(APP_CONFIG.endpoints.upload, fd);
                if (res.status === 'success') {
                    this.files.set(id, res.file_id);
                    this.updateFileChip(id, true);
                    this.updateHiddenInputs();
                } else {
                    this.removeFile(id);
                    this.app.ui.showToast(res.message || 'Upload failed');
                }
            } catch (e) {
                this.removeFile(id);
                this.app.ui.showToast('Upload error');
            }
        }

        renderFileChip(id, name) {
            const html = `
                <div class="file-chip" id="file-${id}">
                    <div class="progress-ring"></div>
                    <span class="file-name" title="${name}">${name}</span>
                    <button type="button" class="btn-close btn-close-white" style="font-size: 0.7rem;" onclick="ollamaApp.uploader.removeFile('${id}')"></button>
                </div>`;
            document.getElementById('upload-list-wrapper').insertAdjacentHTML('beforeend', html);
        }

        updateFileChip(id, success) {
            const chip = document.getElementById(`file-${id}`);
            if (chip) {
                chip.querySelector('.progress-ring').remove();
                chip.querySelector('.file-name').classList.add('text-success');
            }
        }

        async removeFile(id) {
            const serverId = this.files.get(id);
            if (serverId) {
                const fd = new FormData();
                fd.append('file_id', serverId);
                this.app.sendAjax(APP_CONFIG.endpoints.deleteMedia, fd).catch(() => {});
            }
            this.files.delete(id);
            document.getElementById(`file-${id}`)?.remove();
            this.updateHiddenInputs();
        }

        updateHiddenInputs() {
            const container = document.getElementById('uploaded-files-container');
            container.innerHTML = '';
            this.files.forEach(serverId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'uploaded_media[]';
                input.value = serverId;
                container.appendChild(input);
            });
        }
    }

    /**
     * 4. Prompt Manager
     */
    class PromptManager {
        constructor(app) {
            this.app = app;
        }

        init() {
            const select = document.getElementById('savedPrompts');
            const loadBtn = document.getElementById('usePromptBtn');
            const deleteBtn = document.getElementById('deletePromptBtn');

            if (select) {
                select.onchange = () => deleteBtn.disabled = !select.value;
                loadBtn.onclick = () => {
                    const txt = select.value;
                    if (txt) {
                        if (typeof tinymce !== 'undefined') tinymce.get('prompt')?.setContent(txt);
                        else document.getElementById('prompt').value = txt;
                    }
                };
                deleteBtn.onclick = () => this.deletePrompt(select.selectedOptions[0].dataset.id);
            }

            // Auto-populate Save Prompt Modal
            const modal = document.getElementById('savePromptModal');
            if (modal) {
                modal.addEventListener('show.bs.modal', () => {
                    const editor = (typeof tinymce !== 'undefined') ? tinymce.get('prompt') : null;
                    const val = editor ? editor.getContent() : document.getElementById('prompt').value;
                    document.getElementById('modalPromptText').value = val;
                });
            }

            document.querySelector('#savePromptModal form')?.addEventListener('submit', (e) => this.inputSavePrompt(e));
        }

        async inputSavePrompt(e) {
            e.preventDefault();
            const fd = new FormData(e.target);
            try {
                const d = await this.app.sendAjax(e.target.action, fd);
                if (d.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('savePromptModal')).hide();
                    this.app.ui.showToast('Prompt saved!');

                    // Update UI dynamically
                    if (d.prompt) {
                        this.addPromptToUI(d.prompt);
                    }

                    // Clear form
                    e.target.reset();
                } else {
                    this.app.ui.showToast(d.message || 'Save failed');
                }
            } catch (e) {
                this.app.ui.showToast('Error saving prompt');
            }
        }

        addPromptToUI(prompt) {
            const select = document.getElementById('savedPrompts');
            const container = document.getElementById('savedPromptsContainer');
            const emptyAlert = document.getElementById('no-prompts-alert');

            // Show container if it was hidden
            if (container.classList.contains('d-none')) {
                container.classList.remove('d-none');
                emptyAlert.classList.add('d-none');
            }

            // Add new option to select
            const option = document.createElement('option');
            option.value = prompt.prompt_text;
            option.dataset.id = prompt.id;
            option.textContent = prompt.title;
            select.appendChild(option);
        }

        async deletePrompt(id) {
            if (!confirm('Delete this prompt?')) return;
            try {
                const d = await this.app.sendAjax(`${APP_CONFIG.endpoints.deletePromptBase}/${id}`);
                if (d.status === 'success') {
                    this.app.ui.showToast('Prompt deleted');
                    this.removePromptFromUI(id);
                } else {
                    this.app.ui.showToast('Delete failed');
                }
            } catch (e) {
                this.app.ui.showToast('Error deleting prompt');
            }
        }

        removePromptFromUI(id) {
            const select = document.getElementById('savedPrompts');
            const option = select.querySelector(`option[data-id="${id}"]`);

            if (option) {
                option.remove();

                // If no prompts left, show empty state
                if (select.options.length <= 1) { // Only the "Select..." option remains
                    document.getElementById('savedPromptsContainer').classList.add('d-none');
                    document.getElementById('no-prompts-alert').classList.remove('d-none');
                    document.getElementById('deletePromptBtn').disabled = true;
                }

                // Reset select
                select.value = '';
            }
        }
    }

    /**
     * 5. Stream Handler
     */
    class StreamHandler {
        constructor(app) {
            this.app = app;
        }

        async start(fd) {
            this.app.ui.ensureResultCardExists();
            const body = document.getElementById('ai-response-body');
            const raw = document.getElementById('raw-response');
            body.innerHTML = '';
            raw.value = '';

            try {
                // Manually handle fetch for streaming
                if (!fd.has(APP_CONFIG.csrfName)) fd.append(APP_CONFIG.csrfName, this.app.csrfHash);

                const response = await fetch(APP_CONFIG.endpoints.stream, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const {
                        done,
                        value
                    } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value, {
                        stream: true
                    });
                    const lines = (buffer + chunk).split("\n\n");
                    buffer = lines.pop(); // Keep last partial line

                    for (const line of lines) {
                        if (line.startsWith("data: ")) {
                            const data = JSON.parse(line.substring(6));

                            if (data.error) {
                                this.app.ui.injectFlashError(data.error);
                                if (data.csrf_token) this.app.refreshCsrf(data.csrf_token);
                                return;
                            }
                            if (data.csrf_token) this.app.refreshCsrf(data.csrf_token);

                            if (data.text) {
                                const newText = (raw.value + data.text);
                                raw.value = newText;
                                body.innerHTML = marked.parse(newText);
                                this.app.ui.setupAutoScroll();
                            }
                        }
                        if (line.startsWith("event: close")) {
                            // Stream finished
                            this.app.ui.setLoading(false);
                            this.app.ui.setupCodeHighlighting();
                        }
                    }
                }
            } catch (e) {
                console.error(e);
                this.app.ui.injectFlashError("Stream connection interrupted.");
            } finally {
                this.app.ui.setLoading(false);
                this.app.ui.setupCodeHighlighting();
            }
        }
    }

    /**
     * 6. Interaction Handler
     */
    class InteractionHandler {
        constructor(app) {
            this.app = app;
        }

        init() {
            document.getElementById('ollamaForm')?.addEventListener('submit', e => this.handleSubmit(e));
        }

        async handleSubmit(e) {
            e.preventDefault();
            if (typeof tinymce !== 'undefined') tinymce.triggerSave();

            const promptVal = document.getElementById('prompt').value.trim();
            if (!promptVal) {
                this.app.ui.showToast('Please enter a prompt.');
                return;
            }

            this.app.ui.setLoading(true);
            const fd = new FormData(document.getElementById('ollamaForm'));

            if (document.getElementById('streamOutput')?.checked) {
                await this.app.streamer.start(fd);
            } else {
                await this.handleStandard(fd);
            }
        }

        async handleStandard(fd) {
            this.app.ui.ensureResultCardExists();
            try {
                const d = await this.app.sendAjax(APP_CONFIG.endpoints.generate, fd);
                if (d.status === 'success') {
                    document.getElementById('ai-response-body').innerHTML = d.result;
                    document.getElementById('raw-response').value = d.raw_result;
                    this.app.ui.setupCodeHighlighting();
                    this.app.ui.setupAutoScroll();
                    if (d.flash_html) document.getElementById('flash-messages-container').innerHTML = d.flash_html;
                } else {
                    this.app.ui.injectFlashError(d.message || 'Generation failed.');
                }
            } catch (e) {
                this.app.ui.injectFlashError('An error occurred during generation.');
            } finally {
                this.app.ui.setLoading(false);
            }
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', () => new OllamaApp().init());
</script>
<?= $this->endSection() ?>