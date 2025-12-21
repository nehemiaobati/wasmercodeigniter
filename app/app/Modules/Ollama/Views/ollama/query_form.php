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
    | Identical structure to Gemini view for consistency.
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

    /* Tooltip styling */
    .copy-tooltip {
        position: absolute;
        top: -30px;
        right: 0;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
        z-index: 1000;
    }

    .copy-tooltip.show {
        opacity: 1;
    }

    .copy-tooltip::after {
        content: '';
        position: absolute;
        bottom: -4px;
        right: 10px;
        width: 0;
        height: 0;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 4px solid rgba(0, 0, 0, 0.8);
    }

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
     * Ollama Application Controller
     */
    class OllamaApp {
        constructor() {
            this.config = {
                csrfName: '<?= csrf_token() ?>',
                csrfHash: document.querySelector('input[name="<?= csrf_token() ?>"]').value,
                maxFileSize: <?= $maxFileSize ?? 10 * 1024 * 1024 ?>,
                maxFiles: <?= $maxFiles ?? 5 ?>,
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

            this.ui = new UIManager(this);
            this.uploader = new MediaUploader(this);
            this.prompts = new PromptManager(this);
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
            this.config.csrfHash = hash;
            document.querySelectorAll(`input[name="${this.config.csrfName}"]`).forEach(el => el.value = hash);
        }

        async sendAjax(url, data = null) {
            const formData = data instanceof FormData ? data : new FormData();
            if (!formData.has(this.config.csrfName)) formData.append(this.config.csrfName, this.config.csrfHash);

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);

                const d = await res.json();

                const token = d.token || d.csrf_token || res.headers.get('X-CSRF-TOKEN');
                if (token) this.refreshCsrf(token);

                return d;
            } catch (e) {
                console.error('AJAX Error:', e);
                this.ui.showToast('Network error occurred.');
                throw e;
            }
        }
    }

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
                        const d = await this.app.sendAjax(this.app.config.endpoints.settings, fd);
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

            const prompt = document.getElementById('prompt').value.trim();
            if (!prompt) {
                this.app.ui.showToast('Please enter a prompt.');
                return;
            }

            this.app.ui.setLoading(true);
            const fd = new FormData(document.getElementById('ollamaForm'));

            if (document.getElementById('streamOutput')?.checked) await this.handleStreaming(fd);
            else await this.handleStandard(fd);
        }

        async handleStandard(fd) {
            this.app.ui.ensureResultCardExists();
            try {
                const d = await this.app.sendAjax(this.app.config.endpoints.generate, fd);
                if (d.status === 'success') {
                    document.getElementById('ai-response-body').innerHTML = d.result;
                    document.getElementById('raw-response').value = d.raw_result;
                    this.app.ui.setupCodeHighlighting();
                    this.app.ui.setupAutoScroll();
                    if (d.flash_html) document.getElementById('flash-messages-container').innerHTML = d.flash_html;
                    if (d.audio_url) {
                        const ac = document.getElementById('audio-player-container');
                        if (ac) ac.innerHTML = `<div class="alert alert-info d-flex align-items-center mb-4"><i class="bi bi-volume-up-fill fs-4 me-3"></i><audio controls autoplay class="w-100"><source src="${d.audio_url}" type="audio/mpeg"><source src="${d.audio_url}" type="audio/wav">Your browser does not support the audio element.</audio></div>`;
                    }
                } else this.app.ui.injectFlashError(d.message || 'Generation failed.');
            } catch (e) {
                this.app.ui.injectFlashError('An error occurred during generation.');
            } finally {
                this.app.ui.setLoading(false);
                this.app.uploader.clearUploads();
            }
        }

        async handleStreaming(fd) {
            this.app.ui.ensureResultCardExists();
            const resBody = document.getElementById('ai-response-body');
            const rawRes = document.getElementById('raw-response');
            resBody.innerHTML = '';
            rawRes.value = '';

            try {
                const response = await fetch(this.app.config.endpoints.stream, {
                    method: 'POST',
                    body: fd
                });
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                let accum = '';

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
                        part.split('\n').forEach(line => {
                            if (line.startsWith('data: ')) {
                                try {
                                    const d = JSON.parse(line.substring(6));
                                    if (d.text) {
                                        accum += d.text;
                                        resBody.innerHTML = marked.parse(accum);
                                        rawRes.value += d.text;
                                    }
                                    if (d.error) {
                                        this.app.ui.injectFlashError(d.error);
                                    }
                                    if (typeof d.cost !== 'undefined') {
                                        if (parseFloat(d.cost) > 0) {
                                            document.getElementById('flash-messages-container').innerHTML = `<div class="alert alert-success alert-dismissible fade show">KSH ${parseFloat(d.cost).toFixed(2)} deducted.<button class="btn-close" data-bs-dismiss="alert"></button></div>`;
                                        }
                                    }
                                    if (d.audio_url) {
                                        const ac = document.getElementById('audio-player-container');
                                        if (ac) ac.innerHTML = `<div class="alert alert-info d-flex align-items-center mb-4"><i class="bi bi-volume-up-fill fs-4 me-3"></i><audio controls autoplay class="w-100"><source src="${d.audio_url}" type="audio/mpeg"><source src="${d.audio_url}" type="audio/wav">Your browser does not support the audio element.</audio></div>`;
                                    }
                                    if (d.csrf_token) this.app.refreshCsrf(d.csrf_token);
                                } catch (e) {}
                            }
                        });
                    }
                }
                this.app.ui.setupCodeHighlighting();
            } catch (e) {
                this.app.ui.injectFlashError('Stream Connection Lost.');
            } finally {
                this.app.ui.setLoading(false);
                this.app.uploader.clearUploads();
            }
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
            const inp = document.getElementById('media-input-trigger');
            if (!area) return;

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => {
                area.addEventListener(e, ev => {
                    ev.preventDefault();
                    ev.stopPropagation();
                });
            });
            ['dragenter', 'dragover'].forEach(e => area.addEventListener(e, () => area.classList.add('dragover')));
            ['dragleave', 'drop'].forEach(e => area.addEventListener(e, () => area.classList.remove('dragover')));

            area.addEventListener('drop', e => this.handleFiles(e.dataTransfer.files));
            inp.addEventListener('change', e => {
                this.handleFiles(e.target.files);
                inp.value = '';
            });

            document.getElementById('upload-list-wrapper')?.addEventListener('click', e => {
                if (e.target.closest('.remove-btn')) this.removeFile(e.target.closest('.remove-btn'));
            });
        }

        handleFiles(files) {
            const currentCount = document.querySelectorAll('.file-chip').length;
            const queueCount = this.queue.length;
            const availableSlots = this.app.config.maxFiles - (currentCount + queueCount);

            if (files.length > availableSlots) {
                this.app.ui.showToast(`Limit reached: Max ${this.app.config.maxFiles} files.`);
                return;
            }

            Array.from(files).forEach(f => {
                if (this.app.config.supportedMimeTypes.includes(f.type) && f.size <= this.app.config.maxFileSize) {
                    const id = Math.random().toString(36).substr(2, 9);
                    this.queue.push({
                        file: f,
                        ui: this.createBar(f, id),
                        id: id
                    });
                } else {
                    let msg = `Invalid file: ${f.name}`;
                    if (!this.app.config.supportedMimeTypes.includes(f.type)) msg += ' (Unsupported type)';
                    else if (f.size > this.app.config.maxFileSize) msg += ' (File too large)';
                    this.app.ui.showToast(msg);
                }
            });
            if (this.queue.length > 0) this.processQueue();
        }

        createBar(f, id) {
            const d = document.createElement('div');
            d.innerHTML = `<div class="file-chip fade show" id="file-item-${id}"><div class="progress-ring"></div><span class="file-name">${f.name}</span><button type="button" class="btn-close p-1 remove-btn disabled" data-id="${id}"></button></div>`;
            document.getElementById('upload-list-wrapper').appendChild(d.firstChild);
            return document.getElementById(`file-item-${id}`);
        }

        processQueue() {
            if (this.isUploading || this.queue.length === 0) return;
            this.isUploading = true;
            this.perform(this.queue.shift());
        }

        perform(job) {
            const fd = new FormData();
            fd.append(this.app.config.csrfName, this.app.config.csrfHash);
            fd.append('file', job.file);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.app.config.endpoints.upload, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = () => {
                try {
                    const r = JSON.parse(xhr.responseText);
                    if (r.csrf_token) this.app.refreshCsrf(r.csrf_token);
                    if (xhr.status === 200 && r.status === 'success') {
                        this.updateUI(job.ui, 'success');
                        job.ui.querySelector('.remove-btn').dataset.serverFileId = r.file_id;
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'uploaded_media[]';
                        hidden.value = r.file_id;
                        hidden.id = `input-${job.id}`;
                        document.getElementById('uploaded-files-container').appendChild(hidden);
                    } else throw new Error(r.message);
                } catch (e) {
                    this.updateUI(job.ui, 'error');
                }
                this.isUploading = false;
                this.processQueue();
            };
            xhr.send(fd);
        }

        updateUI(ui, status) {
            ui.querySelector('.progress-ring').remove();
            ui.querySelector('.remove-btn').classList.remove('disabled');
            const i = document.createElement('i');
            i.className = status === 'success' ? 'bi bi-check-circle-fill text-success me-2' : 'bi bi-exclamation-circle-fill text-danger me-2';
            ui.prepend(i);
            ui.style.borderColor = status === 'success' ? 'var(--bs-success)' : 'var(--bs-danger)';
        }

        async removeFile(btn) {
            const ui = btn.closest('.file-chip');
            const fid = btn.dataset.serverFileId;
            if (fid) {
                const fd = new FormData();
                fd.append('file_id', fid);
                try {
                    await this.app.sendAjax(this.app.config.endpoints.deleteMedia, fd);
                } catch (e) {}
            }
            ui.remove();
            document.getElementById(`input-${btn.dataset.id}`)?.remove();
        }

        clearUploads() {
            document.getElementById('upload-list-wrapper').innerHTML = '';
            document.getElementById('uploaded-files-container').innerHTML = '';
            this.queue = [];
        }
    }

    class PromptManager {
        constructor(app) {
            this.app = app;
        }

        init() {
            const sel = document.getElementById('savedPrompts');
            const load = document.getElementById('usePromptBtn');
            const del = document.getElementById('deletePromptBtn');

            if (load && sel) load.onclick = () => {
                if (!sel.value) return;
                if (tinymce.get('prompt')) tinymce.get('prompt').setContent(sel.value);
                else {
                    const el = document.getElementById('prompt');
                    el.value = sel.value;
                    el.focus();
                }
            };

            if (sel) sel.onchange = () => del.disabled = !sel.value;
            if (del) del.onclick = () => this.deletePrompt();

            const form = document.querySelector('#savePromptModal form');
            if (form) {
                document.getElementById('savePromptModal').addEventListener('show.bs.modal', () => {
                    const val = tinymce.get('prompt') ? tinymce.get('prompt').getContent() : document.getElementById('prompt').value;
                    document.getElementById('modalPromptText').value = val;
                });
                form.onsubmit = (e) => {
                    e.preventDefault();
                    this.savePrompt(new FormData(form), form.action);
                };
            }
        }

        async savePrompt(fd, action) {
            const m = bootstrap.Modal.getInstance(document.getElementById('savePromptModal'));
            try {
                const d = await this.app.sendAjax(action, fd);
                if (d.status === 'success') {
                    this.app.ui.showToast('Saved!');
                    m.hide();
                    location.reload();
                } else this.app.ui.showToast('Failed to save.');
            } catch (e) {
                this.app.ui.showToast('Error saving.');
            }
        }

        async deletePrompt() {
            const sel = document.getElementById('savedPrompts');
            if (sel && sel.value && confirm('Delete?')) {
                try {
                    const id = sel.options[sel.selectedIndex].dataset.id;
                    const d = await this.app.sendAjax(this.app.config.endpoints.deletePromptBase + id);
                    if (d.status === 'success') {
                        sel.options[sel.selectedIndex].remove();
                        if (sel.options.length <= 1) {
                            sel.value = '';
                            document.getElementById('deletePromptBtn').disabled = true;
                        }
                    }
                } catch (e) {}
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => new OllamaApp().init());
</script>
<?= $this->endSection() ?>