<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('public/assets/highlight/styles/atom-one-dark.min.css') ?>">
<style>
    /* --- Layout Overrides --- */
    #mainNavbar,
    .footer,
    .container.my-4 {
        display: none !important;
    }

    body {
        overflow: hidden;
        padding: 0 !important;
    }

    /* --- Gemini Scoped Layout --- */
    .gemini-view-container {
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
    }

    .gemini-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
        min-width: 0;
        overflow: hidden;
    }

    .gemini-response-area {
        flex: 1;
        overflow-y: auto;
        padding: 2rem;
        scroll-behavior: smooth;
        min-height: 0;
    }

    .gemini-header {
        position: sticky;
        top: 0;
        z-index: 1020;
        background: var(--bs-body-bg);
    }

    .gemini-prompt-area {
        width: 100%;
        background: var(--bs-body-bg);
        border-top: 1px solid var(--bs-border-color);
        padding: 1rem 1.5rem calc(1rem + env(safe-area-inset-bottom));
        z-index: 10;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
    }

    /* --- Sidebar --- */
    .gemini-sidebar {
        width: 350px;
        border-left: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
        overflow-y: auto;
        height: 100%;
        padding: 1.5rem;
        transition: 0.3s ease;
    }

    .gemini-sidebar.collapse:not(.show) {
        display: none;
    }

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

    /* --- Components --- */
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

    pre {
        background: var(--code-bg);
        color: #fff;
        padding: 1rem;
        border-radius: 5px;
        position: relative;
        margin-top: 1rem;
    }

    /* Enhanced Copy Buttons */
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

    /* Copy button animations */
    @keyframes copySuccess {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }

    .copy-btn.success {
        animation: copySuccess 0.3s ease;
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

    /* Model Cards */
    .model-card {
        cursor: pointer;
        transition: 0.2s;
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

    /* Upload Chips */
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

    /* Media Output */
    .media-output-container {
        background-color: var(--bs-tertiary-bg);
        border-radius: 0.5rem;
        padding: 1.5rem;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 300px;
        position: relative;
    }

    .generated-media-item {
        max-height: 500px;
        width: auto;
        max-width: 100%;
        object-fit: contain;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-radius: 4px;
        transition: transform 0.2s;
    }

    .video-wrapper {
        width: 100%;
        max-width: 800px;
        aspect-ratio: 16/9;
        background: #000;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .video-wrapper video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .polling-pulse {
        animation: pulse-border 2s infinite;
    }

    @keyframes pulse-border {
        0% {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
        }

        70% {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
        }

        100% {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="gemini-view-container">

    <!-- Main Content -->
    <div class="gemini-main">
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center px-4 py-2 border-bottom bg-body gemini-header">
            <a href="<?= url_to('home') ?>" class="d-flex align-items-center gap-2 text-decoration-none text-reset">
                <i class="bi bi-stars text-primary fs-4"></i>
                <span class="fw-bold fs-5">AI Studio</span>
            </a>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm theme-toggle"><i class="bi bi-circle-half"></i></button>
                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#geminiSidebar">
                    <i class="bi bi-layout-sidebar-reverse"></i> Settings
                </button>
            </div>
        </div>

        <!-- Response Area -->
        <div class="gemini-response-area" id="response-area-wrapper">
            <div id="flash-messages-container"><?= view('App\Views\partials\flash_messages') ?></div>

            <div id="audio-player-container">
                <?php if (session()->getFlashdata('audio_url')): ?>
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-volume-up-fill fs-4 me-3"></i>
                        <audio controls autoplay class="w-100">
                            <source src="<?= url_to('gemini.serve_audio', session()->getFlashdata('audio_url')) ?>">
                        </audio>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($result = session()->getFlashdata('result')): ?>
                <!-- Server-Side Rendered Text Result -->
                <div class="card blueprint-card shadow-sm border-primary" id="results-card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="bi bi-stars me-2"></i>Studio Output</span>
                        <div class="d-flex gap-2">
                            <!-- Enhanced Copy Button with Dropdown -->
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-light copy-btn" id="copyFullResponseBtn" data-format="text">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                                <button type="button" class="btn btn-sm btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
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
                        <div class="d-flex flex-column gap-1">
                            <small class="text-muted fw-medium">Generated by Google Gemini / Imagen / Veo</small>
                            <small class="text-muted fst-italic" style="font-size: 0.75rem;">
                                <i class="bi bi-info-circle me-1"></i> AI-generated content may be inaccurate. Please verify important information.
                            </small>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center text-muted mt-5 pt-5" id="empty-state">
                    <div class="display-1 text-body-tertiary mb-3"><i class="bi bi-lightbulb"></i></div>
                    <h5>Start Creating</h5>
                    <p>Enter your prompt below to generate text, images, or code.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Prompt Input Area -->
        <div class="gemini-prompt-area">
            <form id="geminiForm" action="<?= url_to('gemini.generate') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <ul class="nav nav-pills nav-sm mb-2" id="generationTabs" role="tablist">
                    <li class="nav-item"><button type="button" class="nav-link active py-2 px-3" data-bs-toggle="tab" data-type="text" data-model="gemini-2.5-flash"><i class="bi bi-chat-text me-2"></i>Text</button></li>
                    <li class="nav-item"><button type="button" class="nav-link py-2 px-3" data-bs-toggle="tab" data-type="image"><i class="bi bi-image me-2"></i>Image</button></li>
                    <li class="nav-item"><button type="button" class="nav-link py-2 px-3" data-bs-toggle="tab" data-type="video"><i class="bi bi-camera-video me-2"></i>Video</button></li>
                </ul>

                <div id="model-selection-area" class="mb-2 d-none">
                    <div id="image-models-grid" class="d-flex gap-2 d-none overflow-auto py-2">
                        <?php foreach ($mediaConfigs as $modelId => $config): ?>
                            <?php if (strpos($config['type'], 'image') !== false): ?>
                                <div class="model-card card p-2" style="min-width: 120px;" data-model="<?= esc($modelId) ?>" data-type="image">
                                    <div class="text-center small"><i class="bi bi-image fs-5 text-primary"></i>
                                        <div class="text-truncate mt-1"><?= esc($config['name']) ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div id="video-models-grid" class="d-flex gap-2 d-none overflow-auto py-2">
                        <?php foreach ($mediaConfigs as $modelId => $config): ?>
                            <?php if ($config['type'] === 'video'): ?>
                                <div class="model-card card p-2" style="min-width: 120px;" data-model="<?= esc($modelId) ?>" data-type="video">
                                    <div class="text-center small"><i class="bi bi-camera-video fs-5 text-danger"></i>
                                        <div class="text-truncate mt-1"><?= esc($config['name']) ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="upload-list-wrapper"></div>
                <div id="uploaded-files-container"></div>

                <div class="d-flex align-items-end gap-2 bg-body-tertiary p-2 rounded-4 border">
                    <div id="mediaUploadArea" class="d-inline-block p-0 border-0 bg-transparent mb-1">
                        <input type="file" id="media-input-trigger" multiple class="d-none">
                        <label for="media-input-trigger" class="btn btn-link text-secondary p-1"><i class="bi bi-paperclip fs-4"></i></label>
                    </div>
                    <div class="flex-grow-1">
                        <input type="hidden" name="model_id" id="selectedModelId" value="gemini-2.0-flash">
                        <input type="hidden" name="generation_type" id="generationType" value="text">
                        <textarea id="prompt" name="prompt" class="form-control border-0 bg-transparent prompt-textarea shadow-none" placeholder="Message Gemini..." rows="1"><?= old('prompt') ?></textarea>
                    </div>
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <button type="button" class="btn btn-link text-secondary p-1" data-bs-toggle="modal" data-bs-target="#savePromptModal"><i class="bi bi-bookmark-plus fs-5"></i></button>
                        <button type="submit" id="generateBtn" class="btn btn-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-arrow-up text-white fs-5"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Sidebar (Settings) -->
    <div class="gemini-sidebar collapse collapse-horizontal show" id="geminiSidebar">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold m-0"><i class="bi bi-sliders"></i> Configuration</h5>
            <button class="btn-close d-lg-none" data-bs-toggle="collapse" data-bs-target="#geminiSidebar"></button>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input setting-toggle" type="checkbox" id="assistantMode" data-key="assistant_mode_enabled" <?= $assistant_mode_enabled ? 'checked' : '' ?>>
            <label class="form-check-label fw-medium" for="assistantMode">Conversational Memory</label>
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input setting-toggle" type="checkbox" id="voiceOutput" data-key="voice_output_enabled" <?= $voice_output_enabled ? 'checked' : '' ?>>
            <label class="form-check-label fw-medium" for="voiceOutput">Voice Output (TTS)</label>
        </div>
        <div class="form-check form-switch mb-4">
            <input class="form-check-input setting-toggle" type="checkbox" id="streamOutput" data-key="stream_output_enabled" <?= $stream_output_enabled ? 'checked' : '' ?>>
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
        <form action="<?= url_to('gemini.memory.clear') ?>" method="post" onsubmit="return confirm('Clear all history?');">
            <?= csrf_field() ?><button type="submit" class="btn btn-outline-danger w-100 btn-sm"><i class="bi bi-trash me-2"></i> Clear History</button>
        </form>
    </div>
</div>

<!-- Hidden Download Forms -->
<form id="downloadForm" method="post" action="<?= url_to('gemini.download_document') ?>" target="_blank" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="raw_response" id="dl_raw"><input type="hidden" name="format" id="dl_format">
</form>

<!-- Modal -->
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

<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3 gemini-toast-container">
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
                    pollMedia: '<?= url_to('gemini.media.poll') ?>'
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
            window.geminiApp = this;
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
                const d = await res.json();
                const token = d.token || d.csrf_token;
                if (token) this.refreshCsrf(token);
                return d;
            } catch (e) {
                console.error('AJAX Error:', e);
                this.ui.showToast('Network error.');
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
            this.setupTabs();
            this.setupSettings();
            this.setupCodeHighlighting();
            this.setupAutoScroll();
            this.setupDownloads();
            this.initTinyMCE();
        }
        handleResponsiveSidebar() {
            if (window.innerWidth < 992) {
                const sb = document.getElementById('geminiSidebar');
                if (sb && sb.classList.contains('show')) sb.classList.remove('show');
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
                max_height: 120,
                setup: (editor) => {
                    editor.on('keydown', (e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            if (editor.getContent().trim()) {
                                editor.save();
                                document.getElementById('geminiForm').requestSubmit();
                            }
                        }
                    });
                    editor.on('init', () => this.updateModelSelectionUI(document.getElementById('generationType').value));
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
        setupTabs() {
            document.querySelectorAll('#generationTabs button').forEach(btn => {
                btn.addEventListener('shown.bs.tab', (e) => {
                    const type = e.target.dataset.type;
                    document.getElementById('generationType').value = type;
                    this.updateModelSelectionUI(type);
                });
            });
            document.querySelectorAll('.model-card').forEach(card => {
                card.addEventListener('click', () => {
                    document.querySelectorAll('.model-card').forEach(c => c.classList.remove('active'));
                    card.classList.add('active');
                    document.getElementById('selectedModelId').value = card.dataset.model;
                });
            });
        }
        updateModelSelectionUI(type) {
            const area = document.getElementById('model-selection-area');
            const imgGrid = document.getElementById('image-models-grid');
            const vidGrid = document.getElementById('video-models-grid');
            const mInput = document.getElementById('selectedModelId');

            area.classList.add('d-none');
            imgGrid.classList.add('d-none');
            vidGrid.classList.add('d-none');

            if (type === 'text') {
                mInput.value = 'gemini-2.0-flash';
                this.setPlaceholder('Message Gemini...');
            } else {
                area.classList.remove('d-none');
                if (type === 'image') {
                    imgGrid.classList.remove('d-none');
                    this.setPlaceholder('Describe the image...');
                    imgGrid.querySelector('.model-card')?.click();
                } else if (type === 'video') {
                    vidGrid.classList.remove('d-none');
                    this.setPlaceholder('Describe the video...');
                    vidGrid.querySelector('.model-card')?.click();
                }
            }
        }
        setPlaceholder(txt) {
            if (tinymce.activeEditor) tinymce.activeEditor.getBody().setAttribute('data-mce-placeholder', txt);
            else document.getElementById('prompt')?.setAttribute('placeholder', txt);
        }
        setupSettings() {
            document.querySelectorAll('.setting-toggle').forEach(t => {
                t.addEventListener('change', async (e) => {
                    const fd = new FormData();
                    fd.append('setting_key', e.target.dataset.key);
                    fd.append('enabled', e.target.checked);
                    try {
                        const d = await this.app.sendAjax(this.app.config.endpoints.settings, fd);
                        this.showToast(d.status === 'success' ? 'Saved.' : 'Error.');
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
                    const code = b.innerText;
                    navigator.clipboard.writeText(code).then(() => {
                        // Visual feedback
                        btn.classList.add('copied');
                        const originalHtml = btn.innerHTML;
                        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied';
                        setTimeout(() => {
                            btn.innerHTML = originalHtml;
                            btn.classList.remove('copied');
                        }, 2000);
                    }).catch(err => {
                        console.error('Copy failed:', err);
                        btn.innerHTML = '<i class="bi bi-x-lg"></i> Failed';
                        setTimeout(() => {
                            btn.innerHTML = '<i class="bi bi-clipboard"></i>';
                        }, 2000);
                    });
                };
                b.parentElement.appendChild(btn);
            });
        }

        /**
         * Fix 1: Ensure we have the correct card type for Text.
         * If the current card is a Media card (missing #ai-response-body), remove it.
         */
        ensureResultCardExists() {
            const existing = document.getElementById('results-card');
            const hasText = document.getElementById('ai-response-body');

            if (existing && !hasText) existing.remove(); // Remove Media card if switching to text

            if (document.getElementById('results-card')) return;

            const container = document.getElementById('response-area-wrapper');
            const emptyState = document.getElementById('empty-state');
            if (emptyState) emptyState.remove();

            const html = `
        <div class="card blueprint-card shadow-sm border-primary" id="results-card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-stars me-2"></i>Studio Output</span>
                <div class="d-flex gap-2">
                    <!-- Enhanced Copy Button with Dropdown -->
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-light copy-btn" id="copyFullResponseBtn" data-format="text">
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                        <button type="button" class="btn btn-sm btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
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
                <div class="d-flex flex-column gap-1">
                    <small class="text-muted fw-medium">Generated by Google Gemini / Imagen / Veo</small>
                    <small class="text-muted fst-italic" style="font-size: 0.75rem;">
                        <i class="bi bi-info-circle me-1"></i> AI-generated content may be inaccurate. Please verify important information.
                    </small>
                </div>
            </div>
        </div>`;
            container.insertAdjacentHTML('beforeend', html);
            this.setupDownloads();
        }

        setupAutoScroll() {
            setTimeout(() => document.getElementById('results-card')?.scrollIntoView({
                behavior: 'smooth'
            }), 100);
        }

        setupDownloads() {
            // Setup download actions
            document.querySelectorAll('.download-action').forEach(btn => {
                btn.onclick = (e) => {
                    e.preventDefault();
                    document.getElementById('dl_raw').value = document.getElementById('raw-response').value;
                    document.getElementById('dl_format').value = e.target.dataset.format;
                    document.getElementById('downloadForm').submit();
                };
            });

            // Enhanced copy functionality with multiple formats
            const setupCopyButton = (btnId) => {
                const cp = document.getElementById(btnId);
                if (!cp) return;

                // Create tooltip element
                const tooltip = document.createElement('div');
                tooltip.className = 'copy-tooltip';
                cp.parentElement.style.position = 'relative';
                cp.parentElement.appendChild(tooltip);

                // Main copy button click
                cp.onclick = () => this.copyToClipboard('text', cp, tooltip);
            };

            setupCopyButton('copyFullResponseBtn');

            // Copy format dropdown actions
            document.querySelectorAll('.copy-format-action').forEach(btn => {
                btn.onclick = (e) => {
                    e.preventDefault();
                    const format = e.target.dataset.format || e.target.closest('[data-format]').dataset.format;
                    const mainBtn = document.getElementById('copyFullResponseBtn');
                    const tooltip = mainBtn?.parentElement.querySelector('.copy-tooltip');
                    this.copyToClipboard(format, mainBtn, tooltip);
                };
            });
        }

        copyToClipboard(format, button, tooltip) {
            const rawResponse = document.getElementById('raw-response');
            const responseBody = document.getElementById('ai-response-body');
            if (!rawResponse || !responseBody) return;

            let contentToCopy = '';
            let successMessage = 'Copied!';

            switch (format) {
                case 'text':
                    // Get plain text by using innerText which strips all HTML
                    // This gives us the rendered text as it appears visually
                    contentToCopy = responseBody.innerText || responseBody.textContent || '';
                    console.log('Copying as plain text:', contentToCopy.substring(0, 200));
                    successMessage = 'Copied as plain text';
                    break;
                case 'markdown':
                    // Get raw markdown content from the hidden textarea
                    contentToCopy = rawResponse.value || '';
                    console.log('Copying as markdown:', contentToCopy.substring(0, 200));
                    successMessage = 'Copied as markdown';
                    break;
                case 'html':
                    // Get rendered HTML
                    contentToCopy = responseBody.innerHTML || '';
                    console.log('Copying as HTML:', contentToCopy.substring(0, 200));
                    successMessage = 'Copied as HTML';
                    break;
                default:
                    contentToCopy = responseBody.innerText || responseBody.textContent || '';
            }

            // Ensure we have content to copy
            if (!contentToCopy.trim()) {
                this.showToast('No content to copy');
                return;
            }

            navigator.clipboard.writeText(contentToCopy).then(() => {
                // Show success animation
                if (button) {
                    button.classList.add('success');
                    const originalHtml = button.innerHTML;
                    button.innerHTML = '<i class="bi bi-check-lg me-1"></i> Copied!';

                    setTimeout(() => {
                        button.innerHTML = originalHtml;
                        button.classList.remove('success');
                    }, 2000);
                }

                // Show tooltip
                if (tooltip) {
                    tooltip.textContent = successMessage;
                    tooltip.classList.add('show');
                    setTimeout(() => {
                        tooltip.classList.remove('show');
                    }, 2000);
                }

                // Also show toast
                this.showToast(successMessage);
            }).catch(err => {
                console.error('Copy failed:', err);
                this.showToast('Copy failed');

                if (tooltip) {
                    tooltip.textContent = 'Copy failed';
                    tooltip.classList.add('show');
                    setTimeout(() => {
                        tooltip.classList.remove('show');
                    }, 2000);
                }
            });
        }
        setLoading(l) {
            this.generateBtn.disabled = l;
            this.generateBtn.innerHTML = l ? '<span class="spinner-border spinner-border-sm text-white"></span>' : '<i class="bi bi-arrow-up text-white fs-5"></i>';
        }

        /**
         * Fix 2: Robust Download Logic for Media.
         * Uses <a> tag with target="_blank" for reliable downloading.
         */
        renderMediaCard(contentHtml, downloadUrl = null, isProcessing = false) {
            const row = document.getElementById('response-area-wrapper');
            const existing = document.getElementById('results-card');
            if (existing) existing.remove();

            const emptyState = document.getElementById('empty-state');
            if (emptyState) emptyState.remove();

            const processingClass = isProcessing ? 'polling-pulse' : '';
            const title = isProcessing ? 'Generating Content...' : 'Studio Output';

            let actions = '';
            if (downloadUrl && !isProcessing) {
                const safeUrl = encodeURI(downloadUrl);
                const finalUrl = safeUrl.includes('serve') ? `${safeUrl}${safeUrl.includes('?')?'&':'?'}download=1` : safeUrl;
                actions = `<a href="${finalUrl}" target="_blank" class="btn btn-sm btn-light text-primary fw-bold text-decoration-none"><i class="bi bi-download me-1"></i> Download</a>`;
            }

            const html = `
        <div class="card blueprint-card mt-4 shadow-sm border-primary ${processingClass}" id="results-card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-stars me-2"></i>${title}</span>
                <div>${actions}</div>
            </div>
            <div class="card-body p-0">
                <div class="media-output-container">${contentHtml}</div>
            </div>
            ${!isProcessing ? `
            <div class="card-footer bg-body border-top text-center py-2">
                <div class="d-flex flex-column gap-1">
                    <small class="text-muted fw-medium">Generated by Google Gemini / Imagen / Veo</small>
                    <small class="text-muted fst-italic" style="font-size: 0.75rem;">
                        <i class="bi bi-info-circle me-1"></i> AI-generated content may be inaccurate. Please verify important information.
                    </small>
                </div>
            </div>` : ''}
        </div>`;
            row.insertAdjacentHTML('beforeend', html);
            document.getElementById('results-card').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        showMediaResult(url, type) {
            let html = '';
            if (type === 'image') html = `<img src="${url}" class="generated-media-item img-fluid" onclick="window.open('${url}','_blank')">`;
            else if (type === 'video') html = `<div class="video-wrapper"><video controls autoplay loop playsinline><source src="${url}" type="video/mp4"></video></div>`;
            this.renderMediaCard(html, url, false);
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
            ['dragenter', 'dragover'].forEach(e => area.addEventListener(e, ev => {
                ev.preventDefault();
                area.classList.add('dragover');
            }));
            ['dragleave', 'drop'].forEach(e => area.addEventListener(e, ev => {
                ev.preventDefault();
                area.classList.remove('dragover');
            }));
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
            let acc = 0;
            Array.from(files).forEach(f => {
                if (this.app.config.supportedMimeTypes.includes(f.type) && f.size <= this.app.config.maxFileSize) {
                    const id = Math.random().toString(36).substr(2, 9);
                    this.queue.push({
                        file: f,
                        ui: this.createBar(f, id),
                        id: id
                    });
                    acc++;
                } else this.app.ui.showToast('Invalid file');
            });
            if (acc > 0) this.processQueue();
        }
        createBar(f, id) {
            const d = document.createElement('div');
            d.id = `file-item-${id}`;
            d.className = 'file-chip fade show';
            d.innerHTML = `<div class="progress-ring"></div><span class="file-name">${f.name}</span><button type="button" class="btn-close p-1 remove-btn disabled" data-id="${id}"></button>`;
            document.getElementById('upload-list-wrapper').appendChild(d);
            return d;
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
            xhr.onreadystatechange = () => {
                if (xhr.readyState === 4) {
                    try {
                        const r = JSON.parse(xhr.responseText);
                        if (r.csrf_token) this.app.refreshCsrf(r.csrf_token);
                        if (xhr.status === 200 && r.status === 'success') {
                            this.updateUI(job.ui, 'success');
                            job.ui.querySelector('.remove-btn').dataset.serverFileId = r.file_id;
                            const i = document.createElement('input');
                            i.type = 'hidden';
                            i.name = 'uploaded_media[]';
                            i.value = r.file_id;
                            i.id = `input-${job.id}`;
                            document.getElementById('uploaded-files-container').appendChild(i);
                        } else this.updateUI(job.ui, 'error', r.message);
                    } catch (e) {
                        this.updateUI(job.ui, 'error');
                    }
                    this.isUploading = false;
                    this.processQueue();
                }
            };
            xhr.send(fd);
        }
        updateUI(ui, status, msg = '') {
            ui.querySelector('.progress-ring').remove();
            ui.querySelector('.remove-btn').classList.remove('disabled');
            const i = document.createElement('i');
            i.className = status === 'success' ? 'bi bi-check-circle-fill text-success me-2' : 'bi bi-exclamation-circle-fill text-danger me-2';
            ui.insertBefore(i, ui.firstChild);
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
                    ui.remove();
                    document.getElementById(`input-${btn.dataset.id}`)?.remove();
                } catch (e) {}
            } else ui.remove();
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
            if (load) load.onclick = () => {
                if (sel && sel.value) {
                    if (tinymce.get('prompt')) tinymce.get('prompt').setContent(sel.value);
                    else {
                        const el = document.getElementById('prompt');
                        el.value = sel.value;
                        el.focus();
                    }
                }
            };
            if (sel) sel.onchange = () => {
                if (del) del.disabled = !sel.value;
            };
            if (del) del.onclick = () => this.deletePrompt();

            const form = document.querySelector('#savePromptModal form');
            if (form) {
                document.getElementById('savePromptModal').addEventListener('show.bs.modal', () => {
                    document.getElementById('modalPromptText').value = tinymce.get('prompt') ? tinymce.get('prompt').getContent() : document.getElementById('prompt').value;
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
                const d = await this.app.sendAjax(action || fd.get('action'), fd);
                if (d.status === 'success') {
                    this.app.ui.showToast('Saved!');
                    m.hide();

                    const container = document.getElementById('savedPromptsContainer');
                    const alert = document.getElementById('no-prompts-alert');
                    if (container) container.classList.remove('d-none');
                    if (alert) alert.classList.add('d-none');

                    const opt = document.createElement('option');
                    opt.value = d.prompt.prompt_text;
                    opt.textContent = d.prompt.title;
                    opt.dataset.id = d.prompt.id;
                    const sel = document.getElementById('savedPrompts');
                    if (sel) {
                        sel.appendChild(opt);
                        sel.value = d.prompt.prompt_text;
                    }
                    const delBtn = document.getElementById('deletePromptBtn');
                    if (delBtn) delBtn.disabled = false;
                } else this.app.ui.showToast(d.message || 'Failed');
            } catch (e) {
                console.error('Save Prompt Error:', e);
                this.app.ui.showToast('Error saving prompt: ' + (e.message || 'System error'));
            }
        }
        async deletePrompt() {
            const sel = document.getElementById('savedPrompts');
            if (sel && sel.selectedIndex !== -1 && confirm('Delete?')) {
                try {
                    const id = sel.options[sel.selectedIndex].dataset.id;
                    const d = await this.app.sendAjax(this.app.config.endpoints.deletePromptBase + id);
                    if (d.status === 'success') {
                        sel.options[sel.selectedIndex].remove();
                        if (sel.options.length <= 1) { // Only "Select..." left
                            document.getElementById('savedPromptsContainer')?.classList.add('d-none');
                            document.getElementById('no-prompts-alert')?.classList.remove('d-none');
                        }
                        sel.value = '';
                        document.getElementById('deletePromptBtn').disabled = true;
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
            document.getElementById('geminiForm')?.addEventListener('submit', e => this.handleSubmit(e));
        }

        async handleSubmit(e) {
            e.preventDefault();
            const type = document.getElementById('generationType').value;
            if (typeof tinymce !== 'undefined') tinymce.triggerSave();
            const prompt = document.getElementById('prompt').value.trim();
            if (!prompt && type === 'text') {
                this.app.ui.showToast('Enter a prompt.');
                return;
            }

            this.app.ui.setLoading(true);
            const fd = new FormData(document.getElementById('geminiForm'));

            if (type === 'text') {
                if (document.getElementById('streamOutput')?.checked) await this.handleStreaming(fd);
                else await this.handleStandard(fd);
            } else {
                if (this.mock(prompt, type)) return;
                await this.handleMedia(fd);
            }
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
                    if (d.audio_url) document.getElementById('audio-player-container').innerHTML = `<div class="alert alert-info d-flex align-items-center mb-4"><i class="bi bi-volume-up-fill fs-4 me-3"></i><audio controls autoplay class="w-100"><source src="${d.audio_url}" type="audio/mpeg"></audio></div>`;
                    else document.getElementById('audio-player-container').innerHTML = '';
                } else this.app.ui.injectFlashError(d.message || 'Error');
            } catch (e) {
                this.app.ui.injectFlashError('System Error');
            }
            this.app.ui.setLoading(false);
            this.app.uploader.clearUploads();
        }

        async handleStreaming(fd) {
            this.app.ui.ensureResultCardExists();
            document.getElementById('ai-response-body').innerHTML = '';
            document.getElementById('audio-player-container').innerHTML = '';
            let accum = '';
            try {
                const res = await fetch(this.app.config.endpoints.stream, {
                    method: 'POST',
                    body: fd
                });
                const reader = res.body.getReader();
                const dec = new TextDecoder();
                let buffer = '';
                while (true) {
                    const {
                        value,
                        done
                    } = await reader.read();
                    if (done) break;
                    buffer += dec.decode(value, {
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
                                        document.getElementById('ai-response-body').innerHTML = marked.parse(accum);
                                        document.getElementById('raw-response').value += d.text;
                                    } else if (d.error) {
                                        if (d.csrf_token) this.app.refreshCsrf(d.csrf_token);
                                        this.app.ui.injectFlashError(d.error);
                                    } else if (d.cost) document.getElementById('flash-messages-container').innerHTML = `<div class="alert alert-success alert-dismissible fade show">KSH ${parseFloat(d.cost).toFixed(2)} deducted.<button class="btn-close" data-bs-dismiss="alert"></button></div>`;
                                    if (d.audio_url) document.getElementById('audio-player-container').innerHTML = `<div class="alert alert-info d-flex align-items-center mb-4"><i class="bi bi-volume-up-fill fs-4 me-3"></i><audio controls autoplay class="w-100"><source src="${d.audio_url}" type="audio/mpeg"></audio></div>`;
                                    if (d.csrf_token) this.app.refreshCsrf(d.csrf_token);
                                } catch (e) {}
                            }
                        });
                    }
                }
                this.app.ui.setupCodeHighlighting();
            } catch (e) {
                this.app.ui.injectFlashError('Stream Error');
            }
            this.app.ui.setLoading(false);
            this.app.uploader.clearUploads();
        }

        async handleMedia(fd) {
            try {
                const res = await fetch(this.app.config.endpoints.generateMedia, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const d = await res.json();
                if (d.token) this.app.refreshCsrf(d.token);
                if (d.status === 'error') {
                    this.app.ui.injectFlashError(d.message);
                    this.app.ui.setLoading(false);
                } else if (d.type === 'image') {
                    this.app.ui.showMediaResult(d.url, 'image');
                    this.app.ui.setLoading(false);
                } else if (d.type === 'video') {
                    this.app.ui.renderMediaCard(`<div class="text-center p-4"><div class="spinner-border text-primary mb-3"></div><h5>Synthesizing Video</h5><p class="text-muted">10-20 seconds...</p></div>`, null, true);
                    this.pollVideo(d.op_id);
                }
            } catch (e) {
                this.app.ui.injectFlashError('Media Failed');
                this.app.ui.setLoading(false);
            }
        }

        pollVideo(opId) {
            const t = setInterval(async () => {
                const fd = new FormData();
                fd.append('op_id', opId);
                try {
                    const d = await this.app.sendAjax(this.app.config.endpoints.pollMedia, fd);
                    if (d.status === 'completed') {
                        clearInterval(t);
                        this.app.ui.showMediaResult(d.url, 'video');
                        this.app.ui.setLoading(false);
                    } else if (d.status === 'failed') {
                        clearInterval(t);
                        this.app.ui.injectFlashError(d.message);
                        this.app.ui.setLoading(false);
                    }
                } catch (e) {
                    clearInterval(t);
                    this.app.ui.setLoading(false);
                }
            }, 5000);
        }

        mock(p, t) {
            const lp = p.toLowerCase();
            if (lp === '<p>test image</p>' && t === 'image') {
                setTimeout(() => this.app.ui.showMediaResult('https://picsum.photos/300/300', 'image'), 1000);
                this.app.ui.setLoading(false);
                return true;
            }
            if (lp === '<p>test video</p>' && t === 'video') {
                setTimeout(() => this.app.ui.showMediaResult('https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', 'video'), 1000);
                this.app.ui.setLoading(false);
                return true;
            }
            return false;
        }
    }
    document.addEventListener('DOMContentLoaded', () => new GeminiApp().init());
</script>
<?= $this->endSection() ?>