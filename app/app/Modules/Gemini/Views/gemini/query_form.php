<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<!-- External Styles -->
<link rel="stylesheet" href="<?= base_url('public/assets/highlight/styles/atom-one-dark.min.css') ?>">

<style>
    /* 
    |--------------------------------------------------------------------------
    | AI Studio Implementation - Internal Styles
    |--------------------------------------------------------------------------
    */

    :root {
        --gemini-header-height: 60px;
        --gemini-sidebar-width: 350px;
        --gemini-code-bg: #282c34;
        --gemini-z-header: 1020;
        --gemini-z-sidebar: 1050;
        --gemini-z-overlay: 1040;
    }

    /* =========================================
       1. Global Layout Overrides
       ========================================= */
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

    /* =========================================
       2. Main Layout Container
       ========================================= */
    .gemini-view-container {
        position: fixed;
        inset: 0;
        height: 100dvh;
        width: 100vw;
        display: flex;
        overflow: hidden;
        z-index: 1000;
        background-color: var(--bs-body-bg);
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

    /* =========================================
       3. Header
       ========================================= */
    .gemini-header {
        position: sticky;
        top: 0;
        z-index: var(--gemini-z-header);
        background: var(--bs-body-bg);
        border-bottom: 1px solid var(--bs-border-color);
        padding: 0.5rem 1.5rem;
        height: var(--gemini-header-height);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* =========================================
       4. Sidebar
       ========================================= */
    .gemini-sidebar {
        width: var(--gemini-sidebar-width);
        border-left: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
        overflow-y: auto;
        height: 100%;
        padding: 1.5rem;
        transition: transform 0.3s ease, margin-right 0.3s ease;
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
            z-index: var(--gemini-z-sidebar);
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
        }
    }

    /* =========================================
       5. Content Areas
       ========================================= */
    .gemini-response-area {
        flex: 1;
        overflow-y: auto;
        padding: 2rem;
        scroll-behavior: smooth;
        min-height: 0;
    }

    .gemini-prompt-area {
        width: 100%;
        background: var(--bs-body-bg);
        border-top: 1px solid var(--bs-border-color);
        padding: 1rem 1.5rem calc(1rem + env(safe-area-inset-bottom));
        z-index: 10;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
    }

    /* =========================================
       6. Components
       ========================================= */
    /* Textarea */
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

    /* Model Cards */
    .model-card {
        cursor: pointer;
        transition: 0.2s;
        border: 2px solid transparent;
        background-color: var(--bs-body-bg);
    }

    .model-card:hover {
        border-color: var(--bs-primary);
        transform: translateY(-2px);
    }

    .model-card.active {
        border-color: var(--bs-primary);
        background-color: var(--bs-primary-bg-subtle);
    }

    /* File Chips */
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

    /* Code Blocks */
    pre {
        background: var(--gemini-code-bg);
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

    /* Memory Stream */
    .memory-item {
        font-size: 0.9rem;
        border-left: 3px solid transparent;
        transition: all 0.2s;
        cursor: default;
        background-color: var(--bs-body-bg);
    }

    .memory-item:hover {
        background-color: var(--bs-tertiary-bg);
    }

    .memory-item.active-context {
        border-left-color: var(--bs-warning);
        background-color: rgba(255, 193, 7, 0.1) !important;
        border-radius: 4px;
    }

    .memory-date-header {
        font-size: 0.75rem;
        font-weight: bold;
        text-transform: uppercase;
        color: var(--bs-secondary);
        margin-top: 1rem;
        margin-bottom: 0.5rem;
        position: sticky;
        top: 0;
        background: var(--bs-body-bg);
        z-index: 5;
        padding-top: 4px;
        padding-bottom: 4px;
    }

    .delete-memory-btn {
        opacity: 0;
        transition: opacity 0.2s;
    }

    .memory-item:hover .delete-memory-btn {
        opacity: 1;
    }

    /* Thinking Blocks */
    .thinking-block {
        background-color: rgba(255, 255, 255, 0.05);
        /* Adaptive in dark mode via BS vars usually, but hardcoded here for contrast on code-bg if mixed */
        border-radius: 4px;
        transition: all 0.2s;
        border: 1px solid var(--bs-border-color);
    }

    [data-bs-theme="light"] .thinking-block {
        background-color: var(--bs-tertiary-bg);
    }

    .thinking-block[open] {
        background-color: rgba(255, 255, 255, 0.1);
    }

    [data-bs-theme="light"] .thinking-block[open] {
        background-color: var(--bs-secondary-bg);
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

    /* Code Blocks */
    pre {
        background: var(--gemini-code-bg);
        color: #fff;
        padding: 1rem;
        border-radius: 5px;
        position: relative;
        margin-top: 1rem;
    }

    .thinking-content {
        white-space: pre-wrap;
        font-family: var(--bs-font-monospace);
        font-size: 0.85rem;
        color: var(--bs-secondary-color);
    }

    /* Background Job Badge */
    .job-badge {
        font-size: 0.75rem;
        padding: 4px 12px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        border: 1px solid transparent;
        max-width: 200px;
    }

    .job-badge.pending {
        background: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
        border-color: var(--bs-primary-border-subtle);
    }

    .job-badge.completed {
        background: var(--bs-success-bg-subtle);
        color: var(--bs-success);
        border-color: var(--bs-success-border-subtle);
        animation: badge-pulse 2s infinite;
    }

    @keyframes badge-pulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4);
        }

        70% {
            transform: scale(1.05);
            box-shadow: 0 0 0 10px rgba(25, 135, 84, 0);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="gemini-view-container">

    <!-- Main Content Area -->
    <main class="gemini-main">
        <!-- Header -->
        <header class="gemini-header">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= url_to('home') ?>" class="d-flex align-items-center gap-2 text-decoration-none text-reset">
                    <i class="bi bi-stars text-primary fs-4"></i>
                    <span class="fw-bold fs-5">AI Studio</span>
                </a>

                <!-- Background Job Indicator -->
                <div id="background-job-container"></div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm theme-toggle" id="themeToggleBtn" title="Toggle Theme">
                    <i class="bi bi-circle-half"></i>
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="sidebarToggleBtn" data-bs-toggle="collapse" data-bs-target="#geminiSidebar">
                    <i class="bi bi-layout-sidebar-reverse"></i> Settings
                </button>
            </div>
        </header>

        <!-- Chat / Response Area -->
        <div class="gemini-response-area" id="response-area-wrapper">
            <div id="flash-messages-container"><?= view('App\Views\partials\flash_messages') ?></div>

            <!-- Audio Player (Conditional) -->
            <div id="audio-player-container">
                <?php if (session()->getFlashdata('audio_url')): ?>
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-volume-up-fill fs-4 me-3"></i>
                        <audio controls autoplay class="w-100">
                            <source src="<?= url_to('gemini.serve_audio', session()->getFlashdata('audio_url')) ?>" type="audio/mpeg">
                            <source src="<?= url_to('gemini.serve_audio', session()->getFlashdata('audio_url')) ?>" type="audio/wav">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Results or Empty State -->
            <?php if ($result = session()->getFlashdata('result')): ?>
                <div class="card blueprint-card shadow-sm border-primary" id="results-card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="bi bi-stars me-2"></i>Studio Output</span>
                        <!-- Toolbar -->
                        <div class="d-flex gap-2">
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-light copy-btn" id="copyFullResponseBtn" data-format="text">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                                <button type="button" class="btn btn-sm btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                                    <span class="visually-hidden">Toggle</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <h6 class="dropdown-header"><i class="bi bi-clipboard me-1"></i> Copy As</h6>
                                    </li>
                                    <li><a class="dropdown-item copy-format-action" href="#" data-format="text">Plain Text</a></li>
                                    <li><a class="dropdown-item copy-format-action" href="#" data-format="markdown">Markdown</a></li>
                                    <li><a class="dropdown-item copy-format-action" href="#" data-format="html">HTML</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <h6 class="dropdown-header"><i class="bi bi-download me-1"></i> Export As</h6>
                                    </li>
                                    <li><a class="dropdown-item download-action" href="#" data-format="pdf">PDF Document</a></li>
                                    <li><a class="dropdown-item download-action" href="#" data-format="docx">Word Document</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Body -->
                    <div class="card-body response-content" id="ai-response-body"><?= $result ?></div>
                    <textarea id="raw-response" name="raw_response" class="d-none"><?= esc(session()->getFlashdata('raw_result')) ?></textarea>
                    <div class="card-footer bg-body border-top text-center py-2">
                        <small class="text-muted fw-medium d-block">Generated by Google Gemini / Imagen / Veo</small>
                        <small class="text-muted" style="font-size: 0.7rem;">AI may make mistakes. Verify important information.</small>
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

        <!-- Prompt Input Area -->
        <div class="gemini-prompt-area">
            <form id="geminiForm" action="<?= url_to('gemini.generate') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <!-- Mode Tabs -->
                <ul class="nav nav-pills nav-sm mb-2" id="generationTabs" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active py-2 px-3" data-bs-toggle="tab" data-type="text" data-model="gemini-2.5-flash">
                            <i class="bi bi-chat-text me-2"></i>Text
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link py-2 px-3" data-bs-toggle="tab" data-type="image">
                            <i class="bi bi-image me-2"></i>Image
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link py-2 px-3" data-bs-toggle="tab" data-type="video">
                            <i class="bi bi-camera-video me-2"></i>Video
                        </button>
                    </li>
                </ul>

                <!-- Model Selection (Dynamic) -->
                <div id="model-selection-area" class="mb-2 d-none">
                    <!-- Image Models -->
                    <div id="image-models-grid" class="d-flex gap-2 d-none overflow-auto py-2">
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
                    </div>
                    <!-- Video Models -->
                    <div id="video-models-grid" class="d-flex gap-2 d-none overflow-auto py-2">
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
                    </div>
                </div>

                <!-- Attachments & Input -->
                <div id="upload-list-wrapper"></div>
                <div id="uploaded-files-container"></div>

                <div class="d-flex align-items-end gap-2 bg-body-tertiary p-2 rounded-4 border">
                    <!-- Attachment Button -->
                    <div id="mediaUploadArea" class="d-inline-block p-0 border-0 bg-transparent mb-1">
                        <input type="file" id="media-input-trigger" name="media_files[]" multiple class="d-none">
                        <label for="media-input-trigger" class="btn btn-link text-secondary p-1" title="Attach files">
                            <i class="bi bi-paperclip fs-4"></i>
                        </label>
                    </div>

                    <!-- Main Text Input -->
                    <div class="flex-grow-1">
                        <input type="hidden" name="model_id" id="selectedModelId" value="gemini-2.0-flash">
                        <input type="hidden" name="generation_type" id="generationType" value="text">
                        <textarea id="prompt" name="prompt" class="form-control border-0 bg-transparent prompt-textarea shadow-none" placeholder="Message Gemini..." rows="1"><?= old('prompt') ?></textarea>
                    </div>

                    <!-- Submit & Save -->
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <button type="button" class="btn btn-link text-secondary p-1" data-bs-toggle="modal" data-bs-target="#savePromptModal" title="Save Prompt">
                            <i class="bi bi-bookmark-plus fs-5"></i>
                        </button>
                        <button type="submit" id="generateBtn" class="btn btn-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" title="Generate">
                            <i class="bi bi-arrow-up text-white fs-5"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <!-- Right Sidebar (Settings & History) -->
    <aside class="gemini-sidebar collapse collapse-horizontal show" id="geminiSidebar">
        <!-- Header with Tabs -->
        <div class="d-flex align-items-center mb-3">
            <ul class="nav nav-pills nav-fill flex-grow-1 p-1 bg-body rounded" id="sidebarTabs" role="tablist" style="font-size: 0.9rem;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-1" id="config-tab" data-bs-toggle="tab" data-bs-target="#config-pane" type="button" role="tab"><i class="bi bi-sliders me-1"></i> Config</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-1" id="memory-tab" data-bs-toggle="tab" data-bs-target="#memory-pane" type="button" role="tab"><i class="bi bi-activity me-1"></i> History</button>
                </li>
            </ul>
            <button class="btn-close ms-2 d-lg-none" data-bs-toggle="collapse" data-bs-target="#geminiSidebar"></button>
        </div>

        <div class="tab-content h-100 overflow-hidden d-flex flex-column">

            <!-- Configuration Pane -->
            <div class="tab-pane fade show active h-100 overflow-auto custom-scrollbar" id="config-pane" role="tabpanel">

                <!-- Toggles -->
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

                <!-- Saved Prompts -->
                <label class="form-label small fw-bold text-uppercase text-muted">Saved Prompts</label>
                <div id="saved-prompts-wrapper">
                    <div class="input-group mb-3 <?= empty($prompts) ? 'd-none' : '' ?>" id="savedPromptsContainer">
                        <select class="form-select form-select-sm" id="savedPrompts">
                            <option value="" disabled selected>Select...</option>
                            <?php if (!empty($prompts)): ?>
                                <?php foreach ($prompts as $p): ?>
                                    <option value="<?= esc($p->prompt_text, 'attr') ?>" data-id="<?= $p->id ?>"><?= esc($p->title) ?></option>
                                <?php endforeach; ?>
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

                <!-- Danger Zone -->
                <form action="<?= url_to('gemini.memory.clear') ?>" method="post" onsubmit="return confirm('Clear all history?');">
                    <?= csrf_field() ?>
                    <button type="submit" id="clearHistorySubmit" class="btn btn-outline-danger w-100 btn-sm"><i class="bi bi-trash me-2"></i> Clear History</button>
                </form>

                <div class="mt-4 pt-4 text-center">
                    <small class="text-muted">AFRIKENKID AI Studio v2</small>
                </div>
            </div>

            <!-- Memory Stream Pane -->
            <div class="tab-pane fade h-100 overflow-auto custom-scrollbar" id="memory-pane" role="tabpanel">
                <div id="memory-loading" class="text-center py-4 d-none">
                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                </div>
                <div id="history-list" class="d-flex flex-column pb-5">
                    <!-- History items will be injected here -->
                    <div class="text-center text-muted small mt-5">
                        <i class="bi bi-clock-history fs-4 mb-2 d-block"></i>
                        Select the History tab to load interactions.
                    </div>
                </div>
            </div>

        </div>
    </aside>
</div>

<!-- Hidden Support Forms -->
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
                <h5 class="modal-title">Save Prompt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Title</label>
                    <input type="text" id="saveGeminiPromptTitle" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Content</label>
                    <textarea name="prompt_text" id="modalPromptText" class="form-control" rows="4" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" id="savePromptBtn" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Global Toasts -->
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
    /**
     * ==========================================
     * Gemini AI Studio - Frontend Application
     * ==========================================
     */

    // Configuration Constants
    // Configuration Constants
    const APP_CONFIG = {
        csrfName: '<?= csrf_token() ?>',
        csrfHash: '<?= csrf_hash() ?>', // Initial hash
        limits: {
            maxFileSize: <?= $maxFileSize ?? 10 * 1024 * 1024 ?>,
            maxFiles: <?= $maxFiles ?? 5 ?>,
            supportedTypes: <?= $supportedMimeTypes ?? '[]' ?>,
        },
        endpoints: {
            upload: '<?= url_to('gemini.upload_media') ?>',
            deleteMedia: '<?= url_to('gemini.delete_media') ?>',
            settings: '<?= url_to('gemini.settings.update') ?>',
            deletePromptBase: '<?= url_to('gemini.prompts.delete', 0) ?>'.slice(0, -1),
            stream: '<?= url_to('gemini.stream') ?>',
            generate: '<?= url_to('gemini.generate') ?>',
            generateMedia: '<?= url_to('gemini.media.generate') ?>',
            pollMedia: '<?= url_to('gemini.media.poll') ?>',
            activeJob: '<?= url_to('gemini.media.active') ?>', // New Endpoint
            history: '<?= url_to('gemini.history.fetch') ?>',
            deleteHistory: '<?= url_to('gemini.history.delete') ?>'
        }
    };

    /**
     * ViewRenderer
     * 
     * Pure static class responsible for generating HTML strings.
     * Decouples UI templating from business logic (Interaction/Stream handlers).
     * 
     * Principles:
     * - Returns HTML strings only (no DOM side effects).
     * - Stateless: Does not store app state.
     * - Secure: Helper methods like escapeHtml prevent XSS in history items.
     */
    class ViewRenderer {
        static escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        static renderResultCard(isMedia = false, title = 'Studio Output', processing = false) {
            const processingClass = processing ? 'polling-pulse' : '';
            const bodyContent = isMedia ?
                `<div class="media-output-container"></div>` :
                `<div class="card-body response-content" id="ai-response-body"></div>
                 <textarea id="raw-response" class="d-none"></textarea>
                 <div class="card-footer bg-body border-top text-center py-2">
                    <small class="text-muted fw-medium d-block">Generated by Google Gemini / Imagen / Veo</small>
                 </div>`;

            const toolbar = isMedia ? '' : `
                <div class="d-flex gap-2">
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-light copy-btn" id="copyFullResponseBtn" data-format="text"><i class="bi bi-clipboard me-1"></i> Copy</button>
                        <button type="button" class="btn btn-sm btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"><span class="visually-hidden">Toggle</span></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header"><i class="bi bi-clipboard me-1"></i> Copy As</h6></li>
                            <li><a class="dropdown-item copy-format-action" href="#" data-format="text">Plain Text</a></li>
                            <li><a class="dropdown-item copy-format-action" href="#" data-format="markdown">Markdown</a></li>
                            <li><a class="dropdown-item copy-format-action" href="#" data-format="html">HTML</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="bi bi-download me-1"></i> Export As</h6></li>
                            <li><a class="dropdown-item download-action" href="#" data-format="pdf">PDF Document</a></li>
                            <li><a class="dropdown-item download-action" href="#" data-format="docx">Word Document</a></li>
                        </ul>
                    </div>
                </div>`;

            return `
                <div class="card blueprint-card shadow-sm border-primary ${processingClass}" id="results-card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="bi bi-stars me-2"></i>${title}</span>
                        ${toolbar}
                    </div>
                    ${bodyContent}
                </div>`;
        }

        static renderAudioPlayer(url) {
            return `
                <div class="alert alert-info d-flex align-items-center mb-4">
                    <i class="bi bi-volume-up-fill fs-4 me-3"></i>
                    <audio controls autoplay class="w-100">
                        <source src="${url}" type="audio/mpeg">
                        <source src="${url}" type="audio/wav">
                        Your browser does not support the audio element.
                    </audio>
                </div>`;
        }

        static renderFileChip(file, id) {
            return `<div class="file-chip fade show" id="file-item-${id}"><div class="progress-ring"></div><span class="file-name">${file.name}</span><button type="button" class="btn-close p-1 remove-btn disabled" data-id="${id}"></button></div>`;
        }

        static renderHistoryHeader(date) {
            const div = document.createElement('div');
            div.className = 'memory-date-header mt-3 mb-2 px-2 py-1 rounded shadow-sm';
            div.textContent = date;
            return div;
        }

        static renderHistoryItem(item) {
            const el = document.createElement('div');
            el.className = 'memory-item p-3 mb-2 rounded border shadow-sm position-relative';
            el.dataset.id = item.unique_id || item.id;
            el.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="text-truncate fw-medium" style="max-width: 85%; font-size: 0.85rem;" title="${this.escapeHtml(item.user_input || item.user_input_raw)}">
                        ${this.escapeHtml(item.user_input || item.user_input_raw)}
                    </div>
                    <button class="btn btn-link text-danger p-0 delete-memory-btn" style="font-size: 0.8rem;" data-id="${item.unique_id || item.id}" title="Forget">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="text-muted text-truncate small" style="opacity: 0.7;">
                    ${this.escapeHtml(item.ai_output || item.ai_output_raw)}
                </div>`;
            return el;
        }

        static renderLoadMoreButton() {
            const div = document.createElement('div');
            div.className = 'text-center py-3';
            div.innerHTML = `
                <button class="btn btn-sm btn-outline-primary load-more-btn">
                    Load More <i class="bi bi-arrow-down-circle ms-1"></i>
                </button>`;
            return div;
        }

        static renderFlashMessage(msg, type = 'danger') {
            return `<div class="alert alert-${type} alert-dismissible fade show">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
        }

        static renderVideoProcessing(elapsed = 0) {
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            const timeStr = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            return `<div class="text-center p-4">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                <h5>Synthesizing Video</h5>
                <p class="text-muted mb-2">Processing your request...</p>
                <div class="progress mb-2" style="height: 4px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         style="width: 100%"></div>
                </div>
                <small class="text-muted">Elapsed: ${timeStr}</small>
            </div>`;
        }

        static renderVideoPlayer(url) {
            return `<div class="video-wrapper position-relative">
                <video controls autoplay loop playsinline class="w-100">
                    <source src="${url}" type="video/mp4">
                </video>
            </div>
            <div class="text-center mt-3">
                <a href="${url}" download="generated-video.mp4" 
                   class="btn btn-primary">
                    <i class="bi bi-download"></i> Download Video
                </a>
            </div>`;
        }

        static renderImage(url) {
            return `<div class="text-center p-3">
                <img src="${url}" class="generated-media-item img-fluid mb-3" 
                     style="cursor: pointer;" onclick="window.open('${url}','_blank')">
                <div>
                    <a href="${url}" download="generated-image.jpg" 
                       class="btn btn-primary">
                        <i class="bi bi-download"></i> Download Image
                    </a>
                </div>
            </div>`;
        }
    }

    /**
     * RequestQueue
     * 
     * Serializes AJAX requests to prevent CSRF race conditions when regeneration is enabled.
     * Ensures only one request processes at a time, using the freshest token.
     */
    class RequestQueue {
        constructor() {
            this.queue = [];
            this.processing = false;
        }

        /**
         * Enqueue a request function to be executed sequentially.
         * @param {Function} fn - Async function to execute
         * @returns {Promise} - Resolves with fn's result
         */
        async enqueue(fn) {
            return new Promise((resolve, reject) => {
                this.queue.push({
                    fn,
                    resolve,
                    reject
                });
                // If not currently processing, start processing the queue
                if (!this.processing) this.process();
            });
        }

        /**
         * Process the next request in the queue.
         * 
         * This method runs recursively, processing one request at a time.
         * Once a request completes (success or failure), it immediately processes the next.
         * This ensures that each request uses the freshest CSRF token from the previous response.
         */
        async process() {
            // Base case: If the queue is empty, stop processing and reset the flag.
            if (this.queue.length === 0) {
                this.processing = false;
                return;
            }

            // Set processing flag to true to prevent multiple concurrent processing loops.
            this.processing = true;
            // Dequeue the next request item (function and its associated promises).
            const {
                fn,
                resolve,
                reject
            } = this.queue.shift();

            try {
                // Execute the enqueued async function.
                const result = await fn();
                // Resolve the promise for the current request.
                resolve(result);
            } catch (e) {
                // Reject the promise for the current request if an error occurs.
                reject(e);
            }

            // Recursively call process to handle the next item in the queue (tail call).
            this.process();
        }
    }

    /**
     * GeminiApp
     * 
     * Main application controller/orchestrator.
     * 
     * Responsibilities:
     * 1. Dependency Injection: Initializes and holds references to all sub-modules (ui, uploader, etc.).
     * 2. State Management: centralized source of truth for CSRF tokens.
     * 3. Communication: Provides the `sendAjax` wrapper for consistent error handling and CSRF rotation.
     * 
     * Pattern: Singleton-like (instantiated once on DOMContentLoaded).
     */
    class GeminiApp {
        constructor() {
            this.csrfHash = APP_CONFIG.csrfHash; // Track current hash
            this.requestQueue = new RequestQueue(); // Serialize AJAX to prevent CSRF race

            // Initialize Sub-Modules
            this.ui = new UIManager(this);
            this.uploader = new MediaUploader(this);
            this.prompts = new PromptManager(this);
            this.history = new HistoryManager(this);
            this.streamer = new StreamHandler(this);
            this.prompts = new PromptManager(this);
            this.history = new HistoryManager(this);
            this.streamer = new StreamHandler(this);
            this.jobs = new JobManager(this); // New Module
            this.interaction = new InteractionHandler(this);
        }

        init() {
            // Setup Libraries
            if (typeof marked !== 'undefined') marked.use({
                breaks: true,
                gfm: true
            });

            // Initialize Modules
            this.ui.init();
            this.uploader.init();
            this.prompts.init();
            this.history.init();
            this.prompts.init();
            this.history.init();
            this.jobs.init(); // Init Jobs
            this.interaction.init();

            // Expose for debugging
            window.geminiApp = this;
        }

        /**
         * Updates the CSRF hash across the application state and all hidden input fields.
         * Critical for preventing 403 Forbidden errors on subsequent requests in SPA-like flows.
         * 
         * @param {string} hash - The new CSRF hash from the server header or JSON response.
         */
        refreshCsrf(hash) {
            if (!hash) return;
            this.csrfHash = hash;
            document.querySelectorAll(`input[name="${APP_CONFIG.csrfName}"]`)
                .forEach(el => el.value = hash);
        }

        /**
         * Unified AJAX Helper
         * 
         * Wraps `fetch` to provide:
         * 1. Auto-appending of CSRF tokens to FormData.
         * 2. X-Requested-With header for CodeIgniter AJAX detection.
         * 3. Automatic CSRF token rotation from response headers/body.
         * 4. Centralized error logging and UI toast notification on failure.
         * 
         * @param {string} url - Endpoint URL
         * @param {FormData|null} data - Payload
         * @returns {Promise<Object>} - Parsed JSON response
         */
        async sendAjax(url, data = null) {
            return this.requestQueue.enqueue(async () => {
                const formData = data instanceof FormData ? data : new FormData();
                if (!formData.has(APP_CONFIG.csrfName)) {
                    formData.append(APP_CONFIG.csrfName, this.csrfHash);
                }

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    let json = null;
                    try {
                        json = await res.json();
                    } catch (e) {
                        /* Not JSON */
                    }

                    // Always attempt CSRF rotation if token is present
                    if (json) {
                        const token = json.token || json.csrf_token || res.headers.get('X-CSRF-TOKEN');
                        if (token) this.refreshCsrf(token);
                    }

                    if (!res.ok) {
                        const errorMsg = json?.message || json?.error || `HTTP Error: ${res.status}`;
                        throw new Error(errorMsg);
                    }

                    return json;
                } catch (e) {
                    console.error("AJAX Failure", e);
                    // Only show toast if it's not a handled validation/logic error from server
                    if (e.message.indexOf('HTTP Error') === 0 || e.message === 'Failed to fetch') {
                        this.ui.showToast('Communication error.');
                    }
                    throw e;
                }
            });
        }
    }

    /**
     * UIManager
     * 
     * Mediator for all DOM manipulations and visual state updates.
     * 
     * specific duties:
     * - Managing Loading States: Toggling buttons/spinners during async operations.
     * - Sidebar/Layout: Handling responsive behavior and tab switching logic.
     * - 3rd Party Libs: Initializing and configuring TinyMCE (editor) and Highlight.js (syntax).
     * - Feedback: Displaying Toasts and Flash messages via ViewRenderer.
     */
    class UIManager {
        constructor(app) {
            this.app = app;
            this.els = {
                generateBtn: document.getElementById('generateBtn'),
                sidebar: document.getElementById('geminiSidebar'),
                responseArea: document.getElementById('response-area-wrapper'),
                toast: document.getElementById('liveToast'),
                flashContainer: document.getElementById('flash-messages-container')
            };
        }

        init() {
            this.setupResponsiveSidebar();
            this.setupTabs();
            this.setupSettingsToggles();
            this.initTinyMCE();
            this.enableCodeFeatures();
            this.setupDownloads();
        }

        setupResponsiveSidebar() {
            if (window.innerWidth < 992 && this.els.sidebar && this.els.sidebar.classList.contains('show')) {
                this.els.sidebar.classList.remove('show');
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
                min_height: 40,
                max_height: 120,
                highlight_on_focus: false,
                content_style: 'body { outline: none !important; }',
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
            if (!this.els.toast) return;
            this.els.toast.querySelector('.toast-body').textContent = msg;
            new bootstrap.Toast(this.els.toast).show();
        }

        setError(msg) {
            if (this.els.flashContainer) this.els.flashContainer.innerHTML = ViewRenderer.renderFlashMessage(msg);
        }

        setLoading(isLoading) {
            const btn = this.els.generateBtn;
            if (!btn) return;
            if (isLoading) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm text-white"></span>';
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-up text-white fs-5"></i>';
            }
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

            let placeholder = 'Message Gemini...';
            if (type === 'text') {
                mInput.value = 'gemini-2.0-flash';
            } else {
                area.classList.remove('d-none');
                if (type === 'image') {
                    imgGrid.classList.remove('d-none');
                    placeholder = 'Describe the image...';
                    imgGrid.querySelector('.model-card')?.click();
                } else if (type === 'video') {
                    vidGrid.classList.remove('d-none');
                    placeholder = 'Describe the video...';
                    vidGrid.querySelector('.model-card')?.click();
                }
            }
            if (tinymce.activeEditor) tinymce.activeEditor.getBody().setAttribute('data-mce-placeholder', placeholder);
            else document.getElementById('prompt')?.setAttribute('placeholder', placeholder);
        }

        enableCodeFeatures() {
            if (typeof hljs !== 'undefined') hljs.highlightAll();
            document.querySelectorAll('pre code').forEach((block) => {
                if (block.parentElement.querySelector('.copy-code-btn')) return;
                const btn = document.createElement('button');
                btn.className = 'btn btn-sm btn-dark copy-code-btn';
                btn.innerHTML = '<i class="bi bi-clipboard"></i>';
                btn.onclick = (e) => {
                    e.preventDefault();
                    navigator.clipboard.writeText(block.innerText).then(() => {
                        btn.classList.add('copied');
                        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                        setTimeout(() => {
                            btn.innerHTML = '<i class="bi bi-clipboard"></i>';
                            btn.classList.remove('copied');
                        }, 2000);
                    });
                };
                block.parentElement.appendChild(btn);
            });
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
            if (mainCopyBtn) {
                mainCopyBtn.onclick = () => this.copyContent('text', mainCopyBtn);
                document.querySelectorAll('.copy-format-action').forEach(btn => {
                    btn.onclick = (e) => {
                        e.preventDefault();
                        this.copyContent(e.target.dataset.format, mainCopyBtn);
                    };
                });
            }
        }

        copyContent(format, btn) {
            const raw = document.getElementById('raw-response');
            const body = document.getElementById('ai-response-body');
            if (!raw || !body) return;

            let content;
            if (format === 'markdown') content = raw.value;
            else if (format === 'html') content = body.innerHTML;
            else {
                const thinkingBlock = body.querySelector('.thinking-block');
                const wasOpen = thinkingBlock ? thinkingBlock.hasAttribute('open') : true;
                if (thinkingBlock && !wasOpen) thinkingBlock.setAttribute('open', '');
                content = body.innerText;
                if (thinkingBlock && !wasOpen) thinkingBlock.removeAttribute('open');
            }

            if (!content.trim()) return this.showToast('Nothing to copy.');
            navigator.clipboard.writeText(content).then(() => {
                this.showToast('Copied!');
                if (btn) {
                    const original = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied';
                    setTimeout(() => btn.innerHTML = original, 2000);
                }
            });
        }

        setupSettingsToggles() {
            document.querySelectorAll('.setting-toggle').forEach(t => {
                t.addEventListener('change', async (e) => {
                    const fd = new FormData();
                    fd.append('setting_key', e.target.dataset.key);
                    fd.append('enabled', e.target.checked);
                    try {
                        const d = await this.app.sendAjax(APP_CONFIG.endpoints.settings, fd);
                        if (d.status !== 'success') this.showToast('Failed to save setting.');
                    } catch (e) {
                        /* Handled */
                    }
                });
            });
        }

        ensureResultCard() {
            const existing = document.getElementById('results-card');
            if (existing) return;
            document.getElementById('empty-state')?.remove();
            this.els.responseArea.insertAdjacentHTML('beforeend', ViewRenderer.renderResultCard());
            this.setupDownloads();
        }

        renderMediaCard(contentHtml, isProcessing = false) {
            const existing = document.getElementById('results-card');
            if (existing) existing.remove();
            document.getElementById('empty-state')?.remove();

            const title = isProcessing ? 'Generating Content...' : 'Studio Output';
            const wrapper = ViewRenderer.renderResultCard(true, title, isProcessing);
            this.els.responseArea.insertAdjacentHTML('beforeend', wrapper);
            const container = this.els.responseArea.querySelector('.media-output-container');
            if (container) container.innerHTML = contentHtml;

            this.scrollToBottom();
        }

        scrollToBottom() {
            setTimeout(() => document.getElementById('results-card')?.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            }), 100);
        }

        renderAudio(url) {
            if (!url) return;
            document.getElementById('audio-player-container').innerHTML = ViewRenderer.renderAudioPlayer(url);
        }
    }

    /**
     * JobManager
     * 
     * Handles background media tasks, persistence, and polling.
     * Decouples "Waiting" from "Interacting".
     */
    class JobManager {
        constructor(app) {
            this.app = app;
            this.activeVideoOp = null;
            this.timers = {
                poller: null,
                ticker: null
            };
        }

        init() {
            this.checkActiveJob();
        }

        isActive() {
            return !!this.activeVideoOp;
        }

        /**
         * Checks server for any interrupted jobs on page load.
         */
        async checkActiveJob() {
            try {
                const d = await this.app.sendAjax(APP_CONFIG.endpoints.activeJob);
                if (d.status === 'success' && d.job) {
                    this.startPolling(d.job.op_id, d.job.elapsed);
                }
            } catch (e) {
                console.warn('Failed to check active jobs', e);
            }
        }

        /**
         * Starts the polling process for a video.
         */
        startPolling(opId, startElapsed = 0) {
            this.activeVideoOp = opId;
            let elapsed = startElapsed;

            // Render Initial Card
            this.app.ui.renderMediaCard(ViewRenderer.renderVideoProcessing(elapsed), true);

            // 1. Ticker (Visual)
            this.timers.ticker = setInterval(() => {
                elapsed++;
                const container = document.querySelector('.media-output-container');
                if (container) container.innerHTML = ViewRenderer.renderVideoProcessing(elapsed);
            }, 1000);

            // 2. Poller (Network)
            this.timers.poller = setInterval(() => this._poll(opId), 5000);
        }

        async _poll(opId) {
            const fd = new FormData();
            fd.append('op_id', opId);

            try {
                const d = await this.app.sendAjax(APP_CONFIG.endpoints.pollMedia, fd);
                if (d.status === 'completed') {
                    this._stop();
                    this.app.ui.renderMediaCard(ViewRenderer.renderVideoPlayer(d.url));
                    this.app.ui.setLoading(false);
                } else if (d.status === 'failed') {
                    throw new Error(d.message);
                }
            } catch (e) {
                this._stop();
                document.getElementById('flash-container').innerHTML = ViewRenderer.renderFlashMessage(e.message || 'Video processing failed.', 'danger');
                this.app.ui.setLoading(false);
            }
        }

        _stop() {
            if (this.timers.ticker) clearInterval(this.timers.ticker);
            if (this.timers.poller) clearInterval(this.timers.poller);
            this.activeVideoOp = null;
        }
    }

    /**
     * InteractionHandler
     * 
     * Orchestrates the user intent flow (Submit -> Validate -> Route -> Execute).
     * 
     * Logic Flow:
     * 1. Intercepts form submission.
     * 2. Syncs TinyMCE content to textarea.
     * 3. Determines generation type (Text vs Media).
     * 4. Routes Text requests to either `generateText` (Standard) or `StreamHandler` (SSE).
     * 5. Routes Media requests to `generateMedia`.
     */
    class InteractionHandler {
        constructor(app) {
            this.app = app;
        }
        init() {
            document.getElementById('geminiForm')?.addEventListener('submit', e => this.handleSubmit(e));
            this.app.jobs.init(); // Check for interrupted video on load
        }

        async handleSubmit(e) {
            e.preventDefault();
            const type = document.getElementById('generationType').value;
            if (typeof tinymce !== 'undefined') tinymce.triggerSave();
            const prompt = document.getElementById('prompt').value.trim();
            if (!prompt && type === 'text') return this.app.ui.showToast('Please enter a prompt.');

            this.app.ui.setLoading(true);
            const fd = new FormData(document.getElementById('geminiForm'));

            try {
                if (type === 'text') {
                    if (document.getElementById('streamOutput')?.checked) await this.app.streamer.start(fd);
                    else await this.generateText(fd);
                } else {
                    await this.generateMedia(fd);
                }
            } catch (e) {
                // UI error handled in calls
            } finally {
                // Only set loading to false if not a video generation, as JobManager handles it for video.
                if (type !== 'video') this.app.ui.setLoading(false);
            }
        }

        async generateText(fd) {
            this.app.ui.ensureResultCard();
            try {
                const d = await this.app.sendAjax(APP_CONFIG.endpoints.generate, fd);
                if (d.status === 'success') {
                    document.getElementById('ai-response-body').innerHTML = d.result;
                    document.getElementById('raw-response').value = d.raw_result;
                    this.app.ui.enableCodeFeatures();
                    this.app.ui.scrollToBottom();
                    if (d.flash_html) this.app.ui.els.flashContainer.innerHTML = d.flash_html;
                    this.app.ui.renderAudio(d.audio_url);

                    if (d.new_interaction_id) this.app.history.addItem({
                        id: d.new_interaction_id,
                        timestamp: d.timestamp,
                        user_input: d.user_input
                    }, d.raw_result);

                    if (d.used_interaction_ids) this.app.history.highlightContext(d.used_interaction_ids);
                } else {
                    this.app.ui.setError(d.message || 'Generation failed.');
                }
            } catch (e) {
                this.app.ui.setError(e.message || 'An error occurred during generation.');
            }
            this.app.uploader.clear();
        }

        async generateMedia(fd) {
            // Frontend Gatekeeper via JobManager
            if (this.app.jobs.isActive()) {
                document.getElementById('flash-container').innerHTML = ViewRenderer.renderFlashMessage('You have a pending video generation. Please wait.', 'warning');
                this.app.ui.setLoading(false);
                return;
            }

            try {
                const d = await this.app.sendAjax(APP_CONFIG.endpoints.generateMedia, fd);
                if (d.status === 'error') throw new Error(d.message);

                if (d.type === 'image') {
                    this.app.ui.renderMediaCard(ViewRenderer.renderImage(d.url));
                } else if (d.type === 'video') {
                    // Hand off to JobManager
                    this.app.jobs.startPolling(d.op_id);
                    return;
                }
            } catch (e) {
                // Unified Error Handling (Flash)
                // If 409 Conflict (Concurrency) or strict error
                let msg = e.message || 'Media Generation Failed';

                // Check if it's the 409 response text
                if (e.message && e.message.includes('pending video')) {
                    document.getElementById('flash-container').innerHTML = ViewRenderer.renderFlashMessage(e.message, 'warning');
                } else {
                    document.getElementById('flash-container').innerHTML = ViewRenderer.renderFlashMessage(msg, 'danger');
                }

                // Clear the main card if it was stuck
                this.app.ui.setLoading(false);
            }
            this.app.uploader.clear();
        }
    }

    /**
     * StreamHandler
     * 
     * Manages Server-Sent Events (SSE) for real-time AI responses.
     * 
     * Core Complexity:
     * - Chunk Parsing: Decodes binary stream chunks into text.
     * - Event Splitting: Separates `data: {...}` lines from the stream buffer.
     * - JSON Validation: Safely parses partial/full JSON objects.
     * - Dual-Mode Rendering: Distinguishes between 'thought' (reasoning models) and 'text' (final answer) 
     *   to render them in separate UI blocks (folding details vs markdown body).
     */
    class StreamHandler {
        constructor(app) {
            this.app = app;
        }

        async start(formData) {
            this.app.ui.ensureResultCard();
            const els = {
                body: document.getElementById('ai-response-body'),
                raw: document.getElementById('raw-response'),
                audio: document.getElementById('audio-player-container')
            };

            els.body.innerHTML = '';
            els.raw.value = '';
            els.audio.innerHTML = '';

            try {
                if (!formData.has(APP_CONFIG.csrfName)) formData.append(APP_CONFIG.csrfName, this.app.csrfHash);
                const response = await fetch(APP_CONFIG.endpoints.stream, {
                    method: 'POST',
                    body: formData
                });
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let accum = '';
                this.currentFullText = '';

                while (true) {
                    const {
                        value,
                        done
                    } = await reader.read();
                    if (done) break;
                    const chunk = decoder.decode(value, {
                        stream: true
                    });
                    accum = this.processChunk(chunk, accum, els);
                }
                this.currentFullText = accum; // For history
                this.app.ui.enableCodeFeatures();
            } catch (e) {
                this.app.ui.setError('Stream Connection Lost.');
            }
            this.app.uploader.clear();
        }

        processChunk(chunk, accum, els) {
            const lines = chunk.split('\n');
            lines.forEach(line => {
                if (line.startsWith('data: ')) {
                    try {
                        const d = JSON.parse(line.substring(6));
                        if (d.thought) {
                            this._ensureThinkingBlock(els.body);
                            this._appendToThinkingBlock(els.body, d.thought);
                            if (!els.raw.value.includes('=== THINKING PROCESS ===')) els.raw.value = '=== THINKING PROCESS ===\n\n' + els.raw.value;
                            els.raw.value += d.thought;
                        } else if (d.text) {
                            accum += d.text;
                            if (els.raw.value.includes('=== THINKING PROCESS ===') && !els.raw.value.includes('=== ANSWER ===')) els.raw.value += '\n\n=== ANSWER ===\n\n';
                            this._preserveThinkingBlockWhileUpdating(els.body, () => {
                                els.body.innerHTML = marked.parse(accum);
                                els.raw.value += d.text;
                            });
                        }
                        if (d.error) this.app.ui.setError(d.error);
                        if (d.cost) this.app.ui.els.flashContainer.innerHTML = ViewRenderer.renderFlashMessage(`KSH ${parseFloat(d.cost).toFixed(2)} deducted.`, 'success');
                        if (d.audio_url) this.app.ui.renderAudio(d.audio_url);
                        if (d.csrf_token) this.app.refreshCsrf(d.csrf_token);

                        // History sync handled here if needed, or at end
                        if (d.new_interaction_id) {
                            this.app.history.addItem({
                                id: d.new_interaction_id,
                                timestamp: d.timestamp,
                                user_input: d.user_input
                            }, accum); // Use current accumulated text
                        }
                        if (d.used_interaction_ids) this.app.history.highlightContext(d.used_interaction_ids);
                    } catch (e) {
                        console.error("Parse error", e);
                    }
                }
            });
            return accum;
        }

        _ensureThinkingBlock(bodyEl) {
            if (bodyEl.querySelector('.thinking-block')) return;
            const details = document.createElement('details');
            details.className = 'thinking-block mb-3';
            details.innerHTML = '<summary class="cursor-pointer text-muted fw-bold small">Thinking Process</summary><div class="thinking-content fst-italic text-muted p-2 border-start mt-1 small"></div>';
            bodyEl.insertBefore(details, bodyEl.firstChild);
        }

        _appendToThinkingBlock(bodyEl, text) {
            const content = bodyEl.querySelector('.thinking-block .thinking-content');
            if (content) content.textContent += text;
        }

        _preserveThinkingBlockWhileUpdating(bodyEl, updateFn) {
            const block = bodyEl.querySelector('.thinking-block');
            updateFn();
            if (block) bodyEl.insertBefore(block, bodyEl.firstChild);
        }
    }
    /**
     * MediaUploader
     * 
     * Manages the file upload workflow with a focus on UX availability options (Drag & Drop + Click).
     * 
     * Features:
     * - Queue System: Uploads files sequentially (one-by-one) to prevent server overload.
     * - UI Sync: Creates visual chips immediately, updates status (spinning -> success/error) asynchronously.
     * - Form Linking: Appends hidden inputs for `file_id`s so the main form knows what to attach to the prompt.
     */
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

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => area.addEventListener(e, ev => {
                ev.preventDefault();
                ev.stopPropagation();
            }));
            ['dragenter', 'dragover'].forEach(e => area.addEventListener(e, () => area.classList.add('dragover')));
            ['dragleave', 'drop'].forEach(e => area.addEventListener(e, () => area.classList.remove('dragover')));

            area.addEventListener('drop', e => this.handleFiles(e.dataTransfer.files));
            inp.addEventListener('change', e => {
                this.handleFiles(e.target.files);
                inp.value = '';
            });
            document.getElementById('upload-list-wrapper')?.addEventListener('click', e => {
                const btn = e.target.closest('.remove-btn');
                if (btn) this.removeFile(btn);
            });
        }

        handleFiles(files) {
            if ((files.length + document.querySelectorAll('.file-chip').length) > APP_CONFIG.limits.maxFiles) return this.app.ui.showToast(`Limit reached.`);
            Array.from(files).forEach(f => {
                if (APP_CONFIG.limits.supportedTypes.includes(f.type) && f.size <= APP_CONFIG.limits.maxFileSize) {
                    const id = Math.random().toString(36).substr(2, 9);
                    const el = document.createElement('div');
                    el.innerHTML = ViewRenderer.renderFileChip(f, id);
                    const chip = el.firstChild;
                    document.getElementById('upload-list-wrapper').appendChild(chip);
                    this.queue.push({
                        file: f,
                        id: id,
                        ui: chip
                    });
                } else this.app.ui.showToast(`Invalid file: ${f.name}`);
            });
            if (this.queue.length) this.processQueue();
        }

        processQueue() {
            if (this.isUploading || !this.queue.length) return;
            this.isUploading = true;
            this.uploadFile(this.queue.shift());
        }

        async uploadFile(job) {
            const fd = new FormData();
            fd.append('file', job.file);

            try {
                const r = await this.app.sendAjax(APP_CONFIG.endpoints.upload, fd);
                if (r.status === 'success') {
                    this.updateChipStatus(job.ui, 'success');
                    job.ui.querySelector('.remove-btn').dataset.serverFileId = r.file_id;
                    this.appendHiddenInput(r.file_id, job.id);
                } else {
                    throw new Error(r.message || 'Upload failed');
                }
            } catch (e) {
                this.updateChipStatus(job.ui, 'error');
                this.app.ui.showToast(e.message || 'Upload failed');
            } finally {
                this.isUploading = false;
                this.processQueue();
            }
        }

        updateChipStatus(ui, status) {
            ui.querySelector('.progress-ring').remove();
            ui.querySelector('.remove-btn').classList.remove('disabled');
            const i = document.createElement('i');
            i.className = status === 'success' ? 'bi bi-check-circle-fill text-success me-2' : 'bi bi-exclamation-circle-fill text-danger me-2';
            ui.prepend(i);
            ui.style.borderColor = status === 'success' ? 'var(--bs-success)' : 'var(--bs-danger)';
        }

        appendHiddenInput(fileId, jobId) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'uploaded_media[]';
            hidden.value = fileId;
            hidden.id = `input-${jobId}`;
            document.getElementById('uploaded-files-container').appendChild(hidden);
        }

        async removeFile(btn) {
            const fid = btn.dataset.serverFileId;
            btn.closest('.file-chip').remove();
            document.getElementById(`input-${btn.dataset.id}`)?.remove();
            if (fid) {
                const fd = new FormData();
                fd.append('file_id', fid);
                this.app.sendAjax(APP_CONFIG.endpoints.deleteMedia, fd).catch(() => {});
            }
        }

        clear() {
            document.getElementById('upload-list-wrapper').innerHTML = '';
            document.getElementById('uploaded-files-container').innerHTML = '';
            this.queue = [];
        }
    }

    /**
     * 6. Prompt Manager
     * Handles loading and saving prompts.
     */
    /**
     * PromptManager
     * 
     * functionality for the "Saved Prompts" CRUD system.
     * 
     * - Operations: Load (into TinyMCE), Save (via Modal), Delete.
     * - UI Sync: Dynamic DOM updates (adding/removing <option> tags) without page reload.
     */
    class PromptManager {
        constructor(app) {
            this.app = app;
        }
        init() {
            document.getElementById('usePromptBtn')?.addEventListener('click', () => {
                const sel = document.getElementById('savedPrompts');
                if (!sel?.value) return;
                if (tinymce.get('prompt')) tinymce.get('prompt').setContent(sel.value);
                else document.getElementById('prompt').value = sel.value;
            });
            const sel = document.getElementById('savedPrompts');
            const delBtn = document.getElementById('deletePromptBtn');
            if (sel && delBtn) {
                sel.onchange = () => delBtn.disabled = !sel.value;
                delBtn.onclick = () => this.deletePrompt(sel);
            }
            const form = document.querySelector('#savePromptModal form');
            if (form) {
                document.getElementById('savePromptModal').addEventListener('show.bs.modal', () => {
                    const val = tinymce.get('prompt') ? tinymce.get('prompt').getContent() : document.getElementById('prompt').value;
                    document.getElementById('modalPromptText').value = val;
                });
                form.onsubmit = async (e) => {
                    e.preventDefault();
                    const m = bootstrap.Modal.getInstance(document.getElementById('savePromptModal'));
                    try {
                        const d = await this.app.sendAjax(form.action, new FormData(form));
                        if (d.status === 'success') {
                            m.hide();
                            this.app.ui.showToast('Prompt saved!');
                            if (d.prompt) this.addPromptToUI(d.prompt);
                            e.target.reset();
                        } else this.app.ui.showToast('Failed to save.');
                    } catch (e) {
                        this.app.ui.showToast('Error saving prompt');
                    }
                };
            }
        }
        addPromptToUI(prompt) {
            const select = document.getElementById('savedPrompts');
            document.getElementById('savedPromptsContainer').classList.remove('d-none');
            document.getElementById('no-prompts-alert').classList.add('d-none');
            const option = document.createElement('option');
            option.value = prompt.prompt_text;
            option.dataset.id = prompt.id;
            option.textContent = prompt.title;
            select.appendChild(option);
        }
        async deletePrompt(sel) {
            if (!sel.value || !confirm('Delete this prompt?')) return;
            try {
                const id = sel.options[sel.selectedIndex].dataset.id;
                const d = await this.app.sendAjax(APP_CONFIG.endpoints.deletePromptBase + id);
                if (d.status === 'success') {
                    this.app.ui.showToast('Prompt deleted');
                    sel.querySelector(`option[data-id="${id}"]`)?.remove();
                } else this.app.ui.showToast('Delete failed');
            } catch (e) {
                this.app.ui.showToast('Error deleting prompt');
            }
        }
    }

    /**
     * 7. History Manager (Memory Stream)
     */
    /**
     * HistoryManager
     * 
     * Manages the "Memory Stream" sidebar functionality.
     * 
     * Logic:
     * - Pagination: Tracks `offset`/`limit` to implementing "Load More" without duplicate fetching.
     * - Date Grouping: Checks timestamps to insert "Today", "Yesterday", etc., headers dynamically.
     * - Context: Highlights specific history items if the AI refers to them in a response (`used_interaction_ids`).
     */
    class HistoryManager {
        static HISTORY_PAGE_SIZE = 5;
        constructor(app) {
            this.app = app;
            this.listEl = document.getElementById('history-list');
            this.loadingEl = document.getElementById('memory-loading');
            this.isLoaded = false;
            this.offset = 0;
            this.limit = HistoryManager.HISTORY_PAGE_SIZE;
            this.hasMore = true;
            this.currentLastDate = '';
        }

        init() {
            document.getElementById('memory-tab')?.addEventListener('shown.bs.tab', () => {
                if (!this.isLoaded) this.fetchHistory();
            });
            this.listEl.addEventListener('click', (e) => {
                const delBtn = e.target.closest('.delete-memory-btn');
                if (delBtn) {
                    e.stopPropagation();
                    this.deleteItem(delBtn.dataset.id);
                    return;
                }
                const loadBtn = e.target.closest('.load-more-btn');
                if (loadBtn) {
                    e.preventDefault();
                    this.loadMore();
                }
            });
        }

        async fetchHistory(append = false) {
            if (!append) {
                this.loadingEl.classList.remove('d-none');
                this.listEl.classList.add('d-none');
            }
            const loadMoreBtn = this.listEl.querySelector('.load-more-btn');
            if (append && loadMoreBtn) {
                loadMoreBtn.disabled = true;
                loadMoreBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
            }
            try {
                const fd = new FormData();
                fd.append('limit', this.limit);
                fd.append('offset', this.offset);
                const d = await this.app.sendAjax(APP_CONFIG.endpoints.history, fd);
                if (d.status === 'success') {
                    this.renderList(d.history, append);
                    this.isLoaded = true;
                    this.offset += d.history.length;
                    this.hasMore = d.history.length === this.limit;
                    this.updateLoadMoreButton();
                }
            } catch (e) {
                if (!append) this.listEl.innerHTML = '<div class="text-center text-danger mt-4"><small>Failed to load history.</small></div>';
            } finally {
                if (!append) {
                    this.loadingEl.classList.add('d-none');
                    this.listEl.classList.remove('d-none');
                }
            }
        }

        renderList(items, append = false) {
            if (!items || items.length === 0) {
                if (!append) this.listEl.innerHTML = '<div class="text-center text-muted mt-5 small">No interaction history yet.</div>';
                return;
            }
            if (!append) {
                this.listEl.innerHTML = '';
                this.currentLastDate = '';
            } else document.querySelector('.load-more-btn')?.remove();

            items.forEach(item => {
                const date = this.formatDate(item.timestamp);
                if (date !== this.currentLastDate) {
                    this.listEl.appendChild(ViewRenderer.renderHistoryHeader(date));
                    this.currentLastDate = date;
                }
                this.listEl.appendChild(ViewRenderer.renderHistoryItem(item));
            });
        }

        updateLoadMoreButton() {
            document.querySelector('.load-more-btn')?.closest('div')?.remove();
            if (this.hasMore) this.listEl.appendChild(ViewRenderer.renderLoadMoreButton());
        }

        formatDate(ts) {
            const date = (typeof ts === 'string' && ts.includes(' ')) ? new Date(ts.replace(' ', 'T')) : new Date(ts);
            if (isNaN(date.getTime())) return 'Today';
            return date.toLocaleDateString(undefined, {
                weekday: 'short',
                month: 'short',
                day: 'numeric'
            });
        }

        async deleteItem(id) {
            if (!confirm('Forget this interaction?')) return;
            const el = this.listEl.querySelector(`.memory-item[data-id="${id}"]`);
            if (el) el.style.opacity = '0.5';
            const fd = new FormData();
            fd.append('unique_id', id);
            try {
                const d = await this.app.sendAjax(APP_CONFIG.endpoints.deleteHistory, fd);
                if (d.status === 'success') el?.remove();
                else {
                    if (el) el.style.opacity = '1';
                    this.app.ui.showToast('Failed to delete.');
                }
            } catch (e) {
                if (el) el.style.opacity = '1';
                this.app.ui.showToast('Error deleting item.');
            }
        }

        highlightContext(ids) {
            this.listEl.querySelectorAll('.active-context').forEach(el => el.classList.remove('active-context'));
            if (!ids || !ids.length) return;
            let firstMatch = null;
            ids.forEach(id => {
                const el = this.listEl.querySelector(`.memory-item[data-id="${id}"]`);
                if (el) {
                    el.classList.add('active-context');
                    if (!firstMatch) firstMatch = el;
                }
            });
            if (firstMatch && document.getElementById('assistantMode').checked) {
                const tabEl = document.getElementById('memory-tab');
                (bootstrap.Tab.getInstance(tabEl) || new bootstrap.Tab(tabEl)).show();
                setTimeout(() => firstMatch.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                }), 300);
            }
        }

        addItem(item, aiRaw) {
            if (this.listEl.querySelector('.text-center.text-muted')) this.listEl.innerHTML = '';
            const dateStr = this.formatDate(item.timestamp);
            let header = this.listEl.querySelector('.memory-date-header');
            if (!header || header.textContent !== dateStr) {
                header = ViewRenderer.renderHistoryHeader(dateStr);
                this.listEl.insertBefore(header, this.listEl.firstChild);
            }
            // Construct partial item to fit interface expected by renderer
            const newItem = {
                unique_id: item.id,
                user_input: item.user_input,
                ai_output: aiRaw
            };
            const el = ViewRenderer.renderHistoryItem(newItem);
            if (header.nextSibling) this.listEl.insertBefore(el, header.nextSibling);
            else this.listEl.appendChild(el);
        }

        async loadMore() {
            await this.fetchHistory(true);
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', () => new GeminiApp().init());
</script>
<?= $this->endSection() ?>