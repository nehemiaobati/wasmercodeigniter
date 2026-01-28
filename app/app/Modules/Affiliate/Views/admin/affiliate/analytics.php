<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<div class="container my-5">
    <div class="blueprint-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1>Analytics: <?= esc($link->title ?: $link->code) ?></h1>
                <p class="text-muted mb-0">
                    <code><?= base_url('amazon/' . $link->code) ?></code>
                </p>
            </div>
            <a href="<?= url_to('admin.affiliate.index') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Links
            </a>
        </div>
    </div>

    <!-- Period Filter -->
    <div class="card blueprint-card mb-4">
        <div class="card-body">
            <form method="get" action="<?= url_to('admin.affiliate.analytics', $link->id) ?>" class="row g-3">
                <div class="col-auto">
                    <label class="form-label">Time Period:</label>
                </div>
                <div class="col-auto">
                    <select name="period" class="form-select" onchange="this.form.submit()">
                        <option value="7days" <?= $period === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="30days" <?= $period === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="90days" <?= $period === '90days' ? 'selected' : '' ?>>Last 90 Days</option>
                        <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>All Time</option>
                    </select>
                </div>
                <div class="col-auto">
                    <span class="badge bg-info fs-5">
                        <i class="bi bi-cursor-fill me-1"></i><?= count($analytics['recent_clicks']) ?> Total Clicks
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- Click Trends -->
    <?php if (!empty($analytics['clicks_by_date'])): ?>
        <div class="card blueprint-card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Click Trends</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Clicks</th>
                                <th>Visual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $maxClicks = max(array_column($analytics['clicks_by_date'], 'count'));
                            foreach ($analytics['clicks_by_date'] as $row):
                            ?>
                                <tr>
                                    <td><?= esc($row->date) ?></td>
                                    <td><span class="badge bg-primary"><?= esc($row->count) ?></span></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar"
                                                style="width: <?= ($row->count / $maxClicks * 100) ?>%">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Top Referrers -->
        <?php if (!empty($analytics['top_referrers'])): ?>
            <div class="col-md-6 mb-4">
                <div class="card blueprint-card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Top Referrers</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($analytics['top_referrers'] as $referrer): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="text-truncate" style="max-width: 70%;">
                                        <?php if ($referrer->referrer): ?>
                                            <a href="<?= esc($referrer->referrer) ?>" target="_blank" class="text-decoration-none">
                                                <?= esc($referrer->referrer) ?>
                                            </a>
                                        <?php else: ?>
                                            <em class="text-muted">Direct / Unknown</em>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-secondary rounded-pill"><?= esc($referrer->count) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Clicks -->
        <div class="col-md-<?= !empty($analytics['top_referrers']) ? '6' : '12' ?> mb-4">
            <div class="card blueprint-card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Clicks (Last 20)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($analytics['recent_clicks'])): ?>
                        <p class="text-muted text-center py-4">No clicks recorded yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Referrer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($analytics['recent_clicks'], 0, 20) as $click): ?>
                                        <tr>
                                            <td class="text-nowrap">
                                                <?= date('M d, Y H:i', strtotime($click->clicked_at)) ?>
                                            </td>
                                            <td class="text-truncate" style="max-width: 300px;">
                                                <?= $click->referrer ? esc($click->referrer) : '<em class="text-muted">Direct</em>' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>