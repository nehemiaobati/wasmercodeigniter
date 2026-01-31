<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container my-5">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= url_to('admin.index') ?>" class="btn btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i> Back</a>
        <h1 class="fw-bold mb-0">Create Email Campaign</h1>
    </div>

    <?php if ($lastQuotaHit): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4 bg-body-tertiary">
            <i class="bi bi-clock-history fs-3 me-3 text-warning"></i>
            <div>
                <h6 class="fw-bold mb-0 text-body">SMTP Health: Cooldown Active</h6>
                <small class="text-secondary">Your last campaign hit a limit on <strong><?= date('M j, Y H:i', strtotime($lastQuotaHit)) ?></strong>.
                    Next healthy send recommended in <span class="fw-bold" id="globalCooldown">Calculating...</span></small>
            </div>
        </div>
    <?php endif; ?>
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card blueprint-card">
                <div class="card-body p-4 p-md-5">
                    <p class="text-muted">Compose your message below or select a saved template to get started. The email will be sent to all registered users.</p>

                    <form action="<?= url_to('admin.campaign.send') ?>" method="post" id="campaignForm">
                        <?= csrf_field() ?>

                        <?php if (!empty($campaigns)): ?>
                            <div class="mb-3">
                                <label for="campaignTemplate" class="form-label fw-bold">Load a Template</label>
                                <div class="input-group">
                                    <select class="form-select" id="campaignTemplate">
                                        <option selected disabled>Select a saved campaign...</option>
                                        <?php foreach ($campaigns as $campaign): ?>
                                            <option value="<?= esc($campaign->id) ?>" data-subject="<?= esc($campaign->subject) ?>" data-body="<?= esc($campaign->body) ?>">
                                                <?= esc($campaign->subject) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-danger" type="button" id="deleteTemplateBtn" disabled>
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="Email Subject" value="<?= old('subject') ?>" required>
                            <label for="subject">Email Subject</label>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="stop_at_count" name="stop_at_count" placeholder="<?= $totalUserCount ?>" value="<?= $totalUserCount ?>">
                                    <label for="stop_at_count">Stop After (Quota Limit)</label>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="bi bi-info-circle"></i> <strong>Recipients:</strong> <?= $totalUserCount ?> total.
                                    Set this lower if you have a tight daily SMTP limit (e.g., 500 for Gmail).
                                </small>
                            </div>
                        </div>

                        <div class="form-floating mb-4">
                            <textarea class="form-control" id="message" name="message" placeholder="Your message here... You can use HTML tags like <strong>, <a>, etc." style="height: 300px" required><?= old('message') ?></textarea>
                            <label for="message">Message Body (HTML supported)</label>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" id="saveTemplateSubmit" class="btn btn-outline-secondary" formaction="<?= url_to('admin.campaign.save') ?>">
                                <i class="bi bi-save"></i> Save as Template
                            </button>
                            <button type="submit" id="sendCampaignSubmit" class="btn btn-primary fw-bold" onclick="return confirm('Are you sure you want to send this campaign to all users?');">
                                <i class="bi bi-send-fill"></i> Send Campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign History Section -->
    <div class="row justify-content-center mt-5">
        <div class="col-lg-9">
            <h4 class="fw-bold mb-3">Recent Campaigns</h4>
            <?php if (!empty($campaigns)): ?>
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-body-tertiary">
                                <tr>
                                    <th class="ps-4">Subject</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($campaigns as $c): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?= esc($c->subject) ?></td>
                                        <td>
                                            <?php
                                            $statusClass = match ($c->status ?? 'draft') {
                                                'completed' => 'success',
                                                'sending' => 'primary',
                                                'pending' => 'warning',
                                                'paused' => 'secondary',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($c->status ?? 'draft') ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $total = ($c->total_recipients ?? 0) > 0 ? $c->total_recipients : 1;
                                            $sent = ($c->sent_count ?? 0) + ($c->error_count ?? 0);
                                            $percent = round(($sent / $total) * 100);
                                            ?>
                                            <?php if (($c->status ?? '') === 'completed'): ?>
                                                <span class="text-success small"><i class="bi bi-check-circle-fill"></i> Done</span>
                                            <?php else: ?>
                                                <div class="progress" style="height: 6px; width: 100px;">
                                                    <div class="progress-bar bg-<?= $statusClass ?>" role="progressbar" style="width: <?= $percent ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?= $percent ?>%</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <?php if (in_array($c->status ?? '', ['pending', 'sending', 'paused'])): ?>
                                                    <a href="<?= url_to('admin.campaign.monitor', $c->id) ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-play-fill"></i> Resume
                                                    </a>
                                                <?php elseif (($c->status ?? '') === 'draft'): ?>
                                                    <a href="#" class="btn btn-sm btn-outline-secondary disabled">Draft</a>
                                                <?php else: ?>
                                                    <span class="text-muted small me-2 mt-1">Completed</span>
                                                <?php endif; ?>

                                                <?php if (($c->error_count ?? 0) > 0): ?>
                                                    <!-- Simplified check for start_retry existence, assuming route exists -->
                                                    <a href="<?= url_to('admin.campaign.start_retry', $c->id) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Retry failed recipients?')">
                                                        <i class="bi bi-arrow-repeat"></i> Retry Failures
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (isset($pager)): ?>
                        <div class="card-footer bg-body-tertiary border-0 py-3 d-flex justify-content-center">
                            <?= $pager->links() ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card shadow-sm border-0 bg-body-tertiary text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary op-25"></i>
                        <p class="mb-0 text-secondary">No campaigns found. Start one above!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const templateSelect = document.getElementById('campaignTemplate');
        const subjectInput = document.getElementById('subject');
        const messageTextarea = document.getElementById('message');
        const deleteButton = document.getElementById('deleteTemplateBtn');
        const csrfToken = document.querySelector('input[name="<?= csrf_token() ?>"]').value;

        if (templateSelect) {
            templateSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];

                deleteButton.disabled = (this.value === "" || selectedOption.disabled);

                if (selectedOption && !selectedOption.disabled) {
                    const subject = selectedOption.getAttribute('data-subject');
                    const body = selectedOption.getAttribute('data-body');

                    if (subject) subjectInput.value = subject;
                    if (body) messageTextarea.value = body;
                }
            });
        }

        if (deleteButton) {
            deleteButton.addEventListener('click', function() {
                const selectedOption = templateSelect.options[templateSelect.selectedIndex];
                if (!selectedOption || selectedOption.disabled) return;

                const campaignId = selectedOption.value;
                const campaignSubject = selectedOption.getAttribute('data-subject');

                // Corrected template literal for confirm message
                if (confirm(`Are you sure you want to delete the template: "${campaignSubject}"? This action cannot be undone.`)) {
                    const tempForm = document.createElement('form');
                    tempForm.method = 'POST';
                    tempForm.action = `<?= rtrim(route_to('admin.campaign.delete', 0), '0') ?>${campaignId}`;

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '<?= csrf_token() ?>';
                    csrfInput.value = csrfToken;
                    tempForm.appendChild(csrfInput);

                    document.body.appendChild(tempForm);
                    tempForm.submit();
                }
            });
        }
        const lastQuotaHit = "<?= $lastQuotaHit ?? '' ?>";
        if (lastQuotaHit) {
            const cooldownEl = document.getElementById('globalCooldown');
            const hitTime = new Date(lastQuotaHit).getTime();

            function updateGlobalTimer() {
                const now = new Date().getTime();
                const diff = (hitTime + (24 * 60 * 60 * 1000)) - now;

                if (diff <= 0) {
                    cooldownEl.innerText = "Healthy Now";
                    cooldownEl.className = "fw-bold text-success";
                } else {
                    const hours = Math.floor(diff / (1000 * 60 * 60));
                    const mins = Math.floor((diff % (100 * 60 * 60)) / (1000 * 60));
                    cooldownEl.innerText = `${hours}h ${mins}m`;
                }
            }
            setInterval(updateGlobalTimer, 60000);
            updateGlobalTimer();
        }
    });
</script>
<?= $this->endSection() ?>