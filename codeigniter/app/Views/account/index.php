<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .account-card {
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
        border: none;
        height: 100%;
    }
    .details-list .list-group-item {
        border-left: 0;
        border-right: 0;
        padding-left: 0;
        padding-right: 0;
    }
    .details-list .list-group-item strong {
        min-width: 100px;
    }
    .details-list .list-group-item i {
        color: var(--primary-color);
        margin-right: 10px;
        width: 20px;
    }
    .table thead {
        background-color: var(--bs-light);
    }
    .table th {
        font-weight: 600;
    }
    .status-badge {
        font-size: 0.8rem;
        padding: 0.4em 0.7em;
    }
    
    /* Improved Pagination Styling */
    .pagination {
        --bs-pagination-padding-x: 0.85rem;
        --bs-pagination-padding-y: 0.45rem;
        --bs-pagination-font-size: 0.95rem;
        --bs-pagination-border-width: 0;
        --bs-pagination-border-radius: 0.375rem;
        --bs-pagination-hover-color: var(--primary-color);
        --bs-pagination-hover-bg: #e9ecef;
        --bs-pagination-active-color: #fff;
        --bs-pagination-active-bg: var(--primary-color);
        --bs-pagination-disabled-color: #6c757d;
        --bs-pagination-disabled-bg: #fff;
    }
    .pagination .page-item {
        margin: 0 4px; /* Adds space between page items */
    }
    .pagination .page-link {
        border-radius: var(--bs-pagination-border-radius) !important; /* Ensure consistent border radius */
        transition: all 0.2s ease-in-out;
    }
    .pagination .page-item.active .page-link {
        box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
        transform: translateY(-2px);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="mb-5">
        <h1 class="fw-bold">My Account</h1>
        <p class="text-muted">Manage your profile details and view your transaction history.</p>
    </div>

    <div class="row g-4">
        <!-- User Details Column -->
        <div class="col-lg-4">
            <div class="card account-card">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Profile Information</h4>
                    <ul class="list-group list-group-flush details-list">
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-person-fill"></i>
                            <strong>Username:</strong>
                            <span class="ms-auto text-muted"><?= esc($user->username) ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-envelope-fill"></i>
                            <strong>Email:</strong>
                            <span class="ms-auto text-muted text-truncate"><?= esc($user->email) ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-wallet2"></i>
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

        <!-- Transaction History Column -->
        <div class="col-lg-8">
            <div class="card account-card d-flex flex-column">
                <div class="card-body p-4 flex-grow-1">
                    <h4 class="fw-bold mb-4">Transaction History</h4>
                    <?php if (!empty($transactions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $index => $transaction): ?>
                                        <tr>
                                            <td><?= esc(date('M d, Y H:i', strtotime($transaction->created_at))) ?></td>
                                            <td>$<?= esc(number_format($transaction->amount, 2)) ?></td>
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
