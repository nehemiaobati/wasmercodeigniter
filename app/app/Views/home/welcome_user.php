<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<style>
    .hover-effect {
        transition: transform 0.2s ease-in-out;
    }

    .hover-effect:hover {
        transform: translateY(-5px);
    }
</style>

<div class="container my-5">

    <?php if (isset($balance) && (float)$balance < 50): ?>
        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <h5 class="alert-heading fw-bold">Your Balance is Low!</h5>
                <p class="mb-0">Don't let your creative flow be interrupted. Top up your account to continue using our services.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4 mb-5">
        <h1 class="fw-bold">Welcome back, <span class="text-primary"><?= esc($username ?? 'User') ?>!</span></h1>
        <p class="lead text-muted">Your digital toolkit is ready. What will you create today?</p>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">

        <!-- AI Studio -->
        <div class="col">
            <div class="card h-100 border-0 shadow-sm hover-effect">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="fs-1 text-primary mb-3"><i class="bi bi-stars"></i></div>
                    <h4 class="fw-bold text-body-emphasis">AI Studio <span class="badge bg-success-subtle text-success-emphasis fs-6 ms-2">New</span></h4>
                    <p class="text-body-secondary">Your creative co-pilot for writing, <strong>image generation</strong>, and document analysis. Powered by Google's Gemini.</p>

                    <!-- Simplified Prompt Suggestion -->
                    <div class="p-3 bg-body-tertiary rounded mb-3 border border-dashed position-relative">
                        <small class="d-block text-body-secondary fw-medium mb-1">Try this prompt:</small>
                        <p class="mb-0 fst-italic me-4" id="promptText">"Write a short marketing email for a new coffee shop in Nairobi."</p>
                        <button class="btn btn-sm btn-link position-absolute top-50 end-0 translate-middle-y text-decoration-none"
                            onclick="copyPrompt()" title="Copy prompt">
                            <i class="bi bi-clipboard" id="copyIcon"></i>
                        </button>
                    </div>

                    <a href="<?= url_to('gemini.index') ?>" class="btn btn-primary mt-auto">Launch AI Studio <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>

        <!-- CryptoQuery -->
        <div class="col">
            <div class="card h-100 border-0 shadow-sm hover-effect">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="fs-1 text-primary mb-3"><i class="bi bi-search"></i></div>
                    <h4 class="fw-bold text-body-emphasis">CryptoQuery</h4>
                    <p class="text-body-secondary">Get instant, real-time balance and transaction history for any public Bitcoin or Litecoin address.</p>
                    <a href="<?= url_to('crypto.index') ?>" class="btn btn-primary mt-auto">Run a Query <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col">
            <div class="card h-100 border-0 shadow-sm hover-effect">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="fs-1 text-primary mb-3"><i class="bi bi-wallet2"></i></div>
                    <h4 class="fw-bold text-body-emphasis">Quick Actions</h4>
                    <div class="text-center my-3">
                        <p class="text-body-secondary text-uppercase small mb-1">Current Balance</p>
                        <div class="fs-1 fw-bold text-success lh-1"><?= esc(number_format((float)($balance ?? 0), 2)) ?></div>
                    </div>
                    <div class="d-grid gap-2 mt-auto">
                        <a href="<?= url_to('payment.index') ?>" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add Funds</a>
                        <a href="<?= url_to('account.index') ?>" class="btn btn-outline-secondary"><i class="bi bi-receipt"></i> View History</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?= $this->section('scripts') ?>
<script>
    function copyPrompt() {
        const text = document.getElementById('promptText').innerText.replace(/^"|"$/g, '');
        const icon = document.getElementById('copyIcon');

        navigator.clipboard.writeText(text).then(() => {
            icon.classList.replace('bi-clipboard', 'bi-check-lg');
            icon.classList.add('text-success');
            setTimeout(() => {
                icon.classList.replace('bi-check-lg', 'bi-clipboard');
                icon.classList.remove('text-success');
            }, 2000);
        });
    }
</script>
<?= $this->endSection() ?>
<?= $this->endSection() ?>