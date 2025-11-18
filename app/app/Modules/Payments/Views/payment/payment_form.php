<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card blueprint-card">
                <div class="card-body p-5">
                    <div class="text-center mb-5">
                        <i class="bi bi-credit-card-2-front-fill text-primary" style="font-size: 3rem;"></i>
                        <h2 class="fw-bold mt-3">Securely Top Up Your Account</h2>
                        <p class="text-muted">Payments are processed by Paystack. Supports <span style="color: green;">M-Pesa</span>, <span style="color: red;">Airtel</span>, and all major cards.</p>
                    </div>

                    <form id="paymentForm" action="<?= url_to('payment.initiate') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" value="<?= esc(old('email', $email)) ?>" required>
                            <label for="email">Email Address</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="number" class="form-control" id="amount" name="amount" placeholder="Amount (in KES)" value="<?= esc(old('amount')) ?>" min="100" required>
                            <label for="amount">Amount to Add (in KES)</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">Proceed to Secure Payment</button>
                        </div>
                        <p class="text-center mt-3 small text-muted"><i class="bi bi-lock-fill"></i> Your financial details are never stored on our servers.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function handleFormSubmit(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            const originalButtonText = submitButton.innerHTML;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...`;
            submitButton.disabled = true;

            window.addEventListener('pageshow', function() {
                submitButton.innerHTML = originalButtonText;
                submitButton.disabled = false;
            });
        }
    }

    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', () => handleFormSubmit(paymentForm));
    }
});
</script>
<?= $this->endSection() ?>
