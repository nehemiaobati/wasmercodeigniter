<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container my-5">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= url_to('admin.campaign.create') ?>" class="btn btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i> Back to Campaigns</a>
        <h1 class="fw-bold mb-0">Campaign Monitor</h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card blueprint-card">
                <div class="card-header bg-body-tertiary py-3">
                    <h5 class="mb-0 text-body">Sending: <span class="fw-bold text-primary"><?= esc($campaign->subject) ?></span></h5>
                </div>
                <div class="card-body p-5">

                    <div class="text-center mb-4">
                        <h2 id="statusHeader" class="fw-bold text-uppercase display-6 text-primary">
                            <?= strtoupper(str_replace('_', ' ', esc($campaign->status))) ?>
                        </h2>
                        <div id="countdownContainer" class="d-none mb-3">
                            <div class="badge bg-warning text-dark p-3 fs-5 border shadow-sm">
                                <i class="bi bi-clock-history me-2"></i>
                                Next Healthy Send in: <span id="cooldownTimer" class="fw-bold">Calculating...</span>
                            </div>
                            <p class="mt-2 text-muted small">
                                You hit the daily limit of <?= esc($campaign->stop_at_count) ?> sends.
                                <br>Resuming will top-up by <strong><?= esc($campaign->quota_increment) ?></strong> recipients.
                            </p>
                        </div>
                        <p class="text-muted" id="statusMessage">
                            <?= $campaign->status === 'completed' ? 'Campaign completed successfully.' : 'Initializing batch process...' ?>
                        </p>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress mb-3" style="height: 30px;">
                        <?php
                        $total = $campaign->total_recipients > 0 ? $campaign->total_recipients : 1;
                        $percent = round((($campaign->sent_count + $campaign->error_count) / $total) * 100, 2);
                        ?>
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar"
                            style="width: <?= $percent ?>%;"
                            aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= $percent ?>%
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="row text-center mt-5">
                        <div class="col-md-4">
                            <h3 class="fw-bold text-success" id="sentCount"><?= esc($campaign->sent_count) ?></h3>
                            <p class="text-muted">Emails Sent</p>
                        </div>
                        <div class="col-md-4">
                            <h3 class="fw-bold text-danger" id="errorCount"><?= esc($campaign->error_count) ?></h3>
                            <p class="text-muted">Failed</p>
                        </div>
                        <div class="col-md-4">
                            <h3 class="fw-bold text-secondary" id="totalCount"><?= esc($campaign->total_recipients) ?></h3>
                            <p class="text-muted">Total Recipients</p>
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="d-flex justify-content-center mt-5 gap-3">
                        <button id="pauseBtn" class="btn btn-warning btn-lg" onclick="handlePause()">
                            <i class="bi bi-pause-fill"></i> Pause
                        </button>
                        <button id="resumeActivityBtn" class="btn btn-success btn-lg d-none" onclick="handleResume()">
                            <i class="bi bi-play-fill"></i> Resume Now
                        </button>
                        <a href="<?= url_to('admin.campaign.create') ?>" id="doneBtn" class="btn btn-secondary btn-lg <?= $campaign->status !== 'completed' ? 'd-none' : '' ?>">
                            Return to Dashboard
                        </a>
                        <button id="retryBtn" class="btn btn-danger btn-lg d-none" onclick="startProcessing()">
                            <i class="bi bi-arrow-clockwise"></i> Retry Connection
                        </button>
                    </div>
                    <div class="text-center mt-3">
                        <a href="javascript:location.reload()" class="text-secondary small"><i class="bi bi-arrow-repeat"></i> Refresh Page Data</a>
                    </div>

                    <!-- Console/Log (Optional) -->
                    <div class="mt-4 p-3 bg-body-tertiary border rounded d-none" id="logConsole" style="max-height: 150px; overflow-y: auto; font-family: monospace; font-size: 0.85rem;">
                        <div class="text-secondary small">Ready to start...</div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const campaignId = <?= esc($campaign->id) ?>;
    let isProcessing = false;
    let failureCount = 0;
    const MAX_FAILURES = 3;

    // Elements
    const progressBar = document.getElementById('progressBar');
    const sentCountEl = document.getElementById('sentCount');
    const errorCountEl = document.getElementById('errorCount');
    const statusHeader = document.getElementById('statusHeader');
    const statusMessage = document.getElementById('statusMessage');
    const retryBtn = document.getElementById('retryBtn');
    const doneBtn = document.getElementById('doneBtn');
    const pauseBtn = document.getElementById('pauseBtn');
    const resumeActivityBtn = document.getElementById('resumeActivityBtn');

    // Prevent accidental close
    window.onbeforeunload = function() {
        if (isProcessing) {
            return "Campaign is currently sending. Leaving this page will pause the process.";
        }
    };

    function updateUI(data) {
        // Update Progress
        progressBar.style.width = data.progress + '%';
        progressBar.innerText = data.progress + '%';
        progressBar.setAttribute('aria-valuenow', data.progress);

        // Update Stats
        sentCountEl.innerText = data.total_sent;
        errorCountEl.innerText = data.total_errors;

        // Update Status
        if (data.quota_hit_at) {
            onQuotaHit(data.quota_hit_at);
        }

        if (data.status === 'completed') {
            onComplete(data.quota_hit_at);
        } else if (data.status === 'paused') {
            onQuotaHit(data.quota_hit_at);
        } else if (data.status === 'retry_mode') {
            statusHeader.innerText = 'RETRYING FAILURES...';
            statusHeader.className = 'fw-bold text-uppercase display-6 text-danger';
            statusMessage.innerText = `Retrying specifically failed recipients. Processed ${data.processed_count} so far...`;
            pauseBtn.classList.remove('d-none');
        } else if (data.status === 'sending') {
            statusHeader.innerText = 'SENDING...';
            statusHeader.className = 'fw-bold text-uppercase display-6 text-primary';
            statusMessage.innerText = `Processed batch of ${data.processed_count}. Continuing...`;
        }
    }

    function onQuotaHit(quotaHitAt) {
        isProcessing = false;

        // Always show the timer banner if quota hit (for health awareness)
        document.getElementById('countdownContainer').classList.remove('d-none');
        startCooldownTimer(quotaHitAt);

        // UI state: only show "QUOTA REACHED" and "RESUME" if we aren't done
        const total = parseInt(document.getElementById('totalCount').innerText) || 0;
        const processed = parseInt(sentCountEl.innerText) + parseInt(errorCountEl.innerText);

        if (processed < total) {
            statusHeader.innerText = 'QUOTA REACHED';
            statusHeader.className = 'fw-bold text-uppercase display-6 text-warning';
            statusMessage.innerText = 'Daily limit reached. Pausing for SMTP health. You can manually override below if needed.';
            pauseBtn.classList.add('d-none');
            resumeActivityBtn.classList.remove('d-none');
        }

        progressBar.classList.remove('progress-bar-animated');
    }

    function startCooldownTimer(quotaHitAt) {
        const timerEl = document.getElementById('cooldownTimer');
        const hitTime = new Date(quotaHitAt).getTime();

        function updateTimer() {
            const now = new Date().getTime();
            const diff = (hitTime + (24 * 60 * 60 * 1000)) - now;

            if (diff <= 0) {
                timerEl.innerText = "READY";
                timerEl.parentElement.className = "badge bg-success text-white p-3 fs-5 border shadow-sm";
            } else {
                const h = Math.floor(diff / (3600000));
                const m = Math.floor((diff % 3600000) / 60000);
                const s = Math.floor((diff % 60000) / 1000);
                timerEl.innerText = `${h}h ${m}m ${s}s`;
            }
        }
        setInterval(updateTimer, 1000);
        updateTimer();
    }

    function onComplete(hasQuotaHit) {
        isProcessing = false;
        statusHeader.innerText = 'COMPLETED';
        statusHeader.className = 'fw-bold text-uppercase display-6 text-success';

        const errors = parseInt(errorCountEl.innerText);
        if (errors > 0) {
            statusMessage.innerHTML = `<span class="text-danger fw-bold">Done with ${errors} errors.</span> Check logs for details.`;
        } else {
            statusMessage.innerText = 'All emails have been processed successfully.';
        }

        progressBar.classList.remove('progress-bar-animated');

        // If quota was hit on the VERY LAST email, still show the timer for situational awareness
        // but DON'T let it overwrite the "COMPLETED" status or show a Resume button.
        if (hasQuotaHit) {
            document.getElementById('countdownContainer').classList.remove('d-none');
            startCooldownTimer(hasQuotaHit);
        }

        pauseBtn.classList.add('d-none');
        resumeActivityBtn.classList.add('d-none'); // Force hide resume on completion
        doneBtn.classList.remove('d-none');

        // Remove unload warning
        window.onbeforeunload = null;
    }

    function onNetworkError() {
        failureCount++;
        statusMessage.innerText = `Connection error. Retrying (${failureCount}/${MAX_FAILURES})...`;
        statusHeader.innerText = 'CONNECTING...';

        if (failureCount >= MAX_FAILURES) {
            isProcessing = false;
            statusHeader.innerText = 'CONNECTION LOST';
            statusHeader.classList.add('text-danger');
            statusMessage.innerText = 'Please check your internet connection and try again.';
            retryBtn.classList.remove('d-none');
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-danger');
        } else {
            // Exponential backoff
            const timeout = Math.pow(2, failureCount) * 1000;
            setTimeout(processBatch, timeout);
        }
    }

    async function handlePause() {
        if (!confirm('Are you sure you want to pause the campaign?')) return;

        try {
            const response = await fetch(`<?= url_to('admin.campaign.pause', $campaign->id) ?>`);
            const data = await response.json();
            if (data.status === 'success') {
                isProcessing = false;
                statusHeader.innerText = 'PAUSED';
                statusHeader.className = 'fw-bold text-uppercase display-6 text-secondary';
                statusMessage.innerText = 'Campaign paused by user.';
                pauseBtn.classList.add('d-none');
                resumeActivityBtn.classList.remove('d-none');
                progressBar.classList.remove('progress-bar-animated');
            }
        } catch (e) {
            console.error(e);
            alert('Failed to pause. Please refresh.');
        }
    }

    async function handleResume() {
        try {
            const response = await fetch(`<?= url_to('admin.campaign.resume', $campaign->id) ?>`);
            const data = await response.json();
            if (data.status === 'success') {
                isProcessing = true;
                resumeActivityBtn.classList.add('d-none');
                pauseBtn.classList.remove('d-none');
                document.getElementById('countdownContainer').classList.add('d-none');
                progressBar.classList.add('progress-bar-animated');
                statusHeader.innerText = 'RESUMING...';
                processBatch();
            }
        } catch (e) {
            console.error(e);
            alert('Failed to resume. Please refresh.');
        }
    }

    async function processBatch() {
        if (!isProcessing) return;

        try {
            const response = await fetch(`<?= url_to('admin.campaign.process_batch', $campaign->id) ?>`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.status === 'error') {
                alert('Server Error: ' + data.message);
                isProcessing = false;
                return;
            }

            failureCount = 0; // Reset on success
            retryBtn.classList.add('d-none'); // Hide retry if visible

            updateUI(data);

            if (data.status !== 'completed') {
                // Throttle: Wait 1 second before next batch to be kind to the server
                setTimeout(processBatch, 1000);
            }

        } catch (error) {
            console.error('Batch processing error:', error);
            onNetworkError();
        }
    }

    function startProcessing() {
        const currentStatus = '<?= esc($campaign->status) ?>';
        const quotaHitAt = '<?= esc($campaign->quota_hit_at ?? "") ?>';

        if (currentStatus === 'completed') {
            onComplete();
        } else if (currentStatus === 'paused' && quotaHitAt) {
            onQuotaHit(quotaHitAt);
        } else {
            isProcessing = true;
            retryBtn.classList.add('d-none');
            resumeActivityBtn.classList.add('d-none');
            pauseBtn.classList.remove('d-none');
            progressBar.classList.remove('bg-danger');
            progressBar.classList.add('progress-bar-animated');
            processBatch();
        }
    }

    // Auto-start on load if not completed
    document.addEventListener('DOMContentLoaded', function() {
        startProcessing();
    });
</script>
<?= $this->endSection() ?>