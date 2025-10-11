<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .payment-section {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .payment-card {
        border-radius: 1rem;
        box-shadow: 0 1rem 3rem rgba(0,0,0,.075);
        border: none;
        width: 100%;
        max-width: 450px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container payment-section">
    <div class="card payment-card">
        <div class="card-body p-5">
            <div class="text-center mb-5">
                <i class="bi bi-credit-card-2-front-fill text-primary" style="font-size: 3rem;"></i>
                <h2 class="fw-bold mt-3">Add Funds to Your Account</h2>
                <p class="text-muted">Securely top up your balance to access our services.</p>
            </div>

            <?= form_open(url_to('payment.initiate')) ?>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" value="<?= esc(old('email', $email)) ?>" required>
                    <label for="email">Email Address</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="number" class="form-control" id="amount" name="amount" placeholder="Amount (KES)" value="<?= esc(old('amount')) ?>" min="100" required>
                    <label for="amount">Amount (KES)</label>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold">Proceed to Payment</button>
                </div>
            <?= form_close() ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>