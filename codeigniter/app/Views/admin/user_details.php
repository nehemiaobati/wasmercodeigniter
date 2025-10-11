<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .details-card, .form-card {
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
        border: none;
    }
    .details-card .list-group-item {
        border: none;
    }
    .details-card .list-group-item strong {
        min-width: 150px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= url_to('admin.index') ?>" class="btn btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i> Back</a>
        <h1 class="fw-bold mb-0">User Details</h1>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card details-card">
                <div class="card-body p-4">
                    <h4 class="card-title fw-bold mb-3">Account Information</h4>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0"><strong>Username:</strong> <span><?= esc($user->username) ?></span></li>
                        <li class="list-group-item d-flex justify-content-between px-0"><strong>Email:</strong> <span><?= esc($user->email) ?></span></li>
                        <li class="list-group-item d-flex justify-content-between px-0"><strong>Current Balance:</strong> <span class="fw-bold h5 text-success mb-0">Ksh. <?= number_format($user->balance, 2) ?></span></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card form-card">
                 <div class="card-body p-4">
                    <h4 class="card-title fw-bold mb-3">Update Balance</h4>
                    <form action="<?= url_to('admin.users.update_balance', $user->id) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" step="0.01" name="amount" id="amount" class="form-control" placeholder="Amount" required>
                                    <label for="amount">Amount</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="action" id="action" class="form-select" required>
                                        <option value="deposit">Deposit</option>
                                        <option value="withdraw">Withdraw</option>
                                    </select>
                                    <label for="action">Action</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Update Balance</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
