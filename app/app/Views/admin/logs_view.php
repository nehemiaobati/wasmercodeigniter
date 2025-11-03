<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .log-container {
        background-color: #1e1e1e;
        color: #d4d4d4;
        font-family: Consolas, 'Courier New', monospace;
        font-size: 0.9rem;
        padding: 1.5rem;
        border-radius: 0.5rem;
        max-height: 70vh;
        overflow-y: auto;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold">Application Logs</h1>
        <a href="<?= url_to('admin.index') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="card blueprint-card">
        <div class="card-body p-4">
            <form method="get" action="<?= url_to('admin.logs') ?>">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="logFileSelect" class="form-label">Select Log File:</label>
                        <select name="file" id="logFileSelect" class="form-select">
                            <?php if (!empty($logFiles)): ?>
                                <?php foreach ($logFiles as $file): ?>
                                    <option value="<?= esc($file) ?>" <?= ($file === $selectedFile) ? 'selected' : '' ?>>
                                        <?= esc($file) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option disabled selected>No log files found</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">View Log</button>
                    </div>
                </div>
            </form>

            <hr class="my-4">

            <?php if ($selectedFile): ?>
                <h4 class="mb-3">Viewing: <span class="text-primary"><?= esc($selectedFile) ?></span></h4>
                <div class="log-container">
                    <?= esc($logContent) ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    The log directory is empty. No log content to display.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>