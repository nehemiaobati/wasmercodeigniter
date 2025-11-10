<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    /* MODIFICATION: The .details-list styling is removed. The card's padding is sufficient. */
    .status-badge {
        font-size: 0.8rem;
        padding: 0.4em 0.7em;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="blueprint-header mb-5">
        <h1 class="fw-bold">My Account</h1>
        <p class="text-muted lead">Manage your profile details and view your transaction history.</p>
    </div>

    <div class="row g-4">
        <!-- User Details Column -->
        <div class="col-lg-4">
            <div class="card blueprint-card">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Profile Information</h4>
                    <!-- MODIFICATION: Replaced custom list with Bootstrap utilities -->
                    <ul class="list-unstyled">
                        <li class="d-flex align-items-center py-2 border-bottom">
                            <i class="bi bi-person-fill text-primary me-3"></i>
                            <strong>Username:</strong>
                            <span class="ms-auto text-muted"><?= esc($user->username) ?></span>
                        </li>
                        <li class="d-flex align-items-center py-2 border-bottom">
                            <i class="bi bi-envelope-fill text-primary me-3"></i>
                            <strong>Email:</strong>
                            <span class="ms-auto text-muted text-truncate"><?= esc($user->email) ?></span>
                        </li>
                        <li class="d-flex align-items-center py-2">
                            <i class="bi bi-wallet2 text-primary me-3"></i>
                            <strong>Balance:</strong>
                            <span class="ms-auto fw-bold h5 text-success mb-0">Ksh. <?= esc(number_format($user->balance, 2)) ?></span>
                        </li>
                    </ul>
                    <div class="d-grid mt-4">
                        <a href="<?= url_to('payment.index') ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Funds</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History Column (No changes needed here) -->
        <div class="col-lg-8">
            <div class="card blueprint-card d-flex flex-column">
                <div class="card-body p-4 flex-grow-1">
                    <h4 class="fw-bold mb-4">Transaction History</h4>
                    <?php if (!empty($transactions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-semibold">Date</th>
                                        <th class="fw-semibold">Amount (KES)</th>
                                        <th class="fw-semibold">Status</th>
                                        <th class="fw-semibold">Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $index => $transaction): ?>
                                        <tr>
                                            <td><?= esc(date('M d, Y H:i', strtotime($transaction->created_at))) ?></td>
                                            <td><?= esc(number_format($transaction->amount, 2)) ?></td>
                                            <td>
                                                <?php
                                                    $status = strtolower($transaction->status);
                                                    $badge_class = 'bg-secondary';
                                                    if ($status === 'success') {
                                                        $badge_class = 'bg-success';
                                                    } elseif ($status === 'pending') {
                                                        $badge_class = 'bg-warning text-dark';
                                                    } elseif ($status === 'failed') {
                                                        $badge_class = 'bg-danger';
                                                    }
                                                ?>
                                                <span class="badge rounded-pill <?= $badge_class ?> status-badge"><?= esc(ucfirst($status)) ?></span>
                                            </td>
                                            <td class="text-muted"><small><?= esc($display_references[$index] ?? 'N/A') ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted p-5">
                            <i class="bi bi-receipt fs-1"></i>
                            <p class="mt-3 mb-0">No transactions found yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (isset($pager) && $pager->getPageCount() > 1): ?>
                    <div class="card-footer bg-transparent border-0 d-flex justify-content-center py-3">
                        <?= $pager->links() ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
