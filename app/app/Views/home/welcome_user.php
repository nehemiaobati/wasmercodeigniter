<?= '
' ?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    /* MODIFICATION: The entire <style> block has been removed. 
       All styling is now handled by Bootstrap 5 utility classes below. */
    .prompt-suggestion {
        background-color: var(--bs-tertiary-bg);
        border: 1px dashed var(--bs-border-color);
        border-radius: 0.5rem;
        padding: 1rem;
        font-size: 0.9rem;
        margin-top: 1.5rem;
        position: relative;
    }
    .prompt-suggestion .copy-btn {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        cursor: pointer;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $tips = [
        "**Conversational Memory:** Enable 'Conversational Memory' in the AI Studio to have the AI remember past interactions for better follow-up questions.",
        "**Multi-File Uploads:** Did you know you can upload multiple files (images, PDFs, etc.) to the AI Studio for comprehensive analysis in a single prompt?",
        "**Saving Prompts:** Save your most-used prompts in the AI Studio to reuse them later, saving you time and effort.",
        "**Crypto Transactions:** When querying crypto transactions, you can specify the number of recent transactions you want to see (up to 50).",
        "**Precise Balance:** All account balance calculations use high-precision math to ensure every cent is accurately tracked."
    ];
    $randomTip = $tips[array_rand($tips)];
?>
<div class="container my-5">

    <?php if (isset($balance) && (float)$balance < 50): ?>
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
        <div>
            <h5 class="alert-heading fw-bold">Your Balance is Low!</h5>
            <p class="mb-0">Don't let your creative flow be interrupted. Top up your account to continue using our services without a hitch.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="blueprint-header text-center mt-4">
        <h1 class="fw-bold">Welcome back, <span class="text-primary"><?= esc($username ?? 'User') ?>!</span></h1>
        <p class="lead text-muted">Your digital toolkit is ready. What will you create today?</p>
    </div>

    <div class="alert alert-primary mb-5">
        <p class="fw-bold mb-1"><i class="bi bi-lightbulb-fill"></i> Pro Tip:</p>
        <p class="mb-0"><?= esc(str_replace('**', '', $randomTip)) ?></p>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <div class="col">
            <div class="card blueprint-card h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="fs-1 text-primary mb-3"><i class="bi bi-stars"></i></div>
                    <h4 class="fw-bold text-body-emphasis">AI Studio</h4>
                    <p class="text-body-secondary">Your creative co-pilot for writing, analysis, and brainstorming. Powered by Google's Gemini.</p>
                    <div class="prompt-suggestion">
                        <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill copy-btn" id="copyPromptBtn" title="Copy prompt">
                            <i class="bi bi-clipboard"></i>
                        </span>
                        <small class="d-block text-body-secondary fw-medium">Try this prompt:</small>
                        <p class="mb-0" id="promptToCopy">"Write a short, engaging marketing email for a new coffee shop opening in Nairobi."</p>
                    </div>
                    <a href="<?= url_to('gemini.index') ?>" class="btn btn-primary mt-4">Launch AI Studio <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card blueprint-card h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="fs-1 text-primary mb-3"><i class="bi bi-search"></i></div>
                    <h4 class="fw-bold text-body-emphasis">CryptoQuery</h4>
                    <p class="text-body-secondary">Get instant, real-time balance and transaction history for any public Bitcoin or Litecoin address.</p>
                    <a href="<?= url_to('crypto.index') ?>" class="btn btn-primary mt-auto">Run a Query <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card blueprint-card h-100">
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

    <div class="card blueprint-card mt-5">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-3 text-center text-body-emphasis">Account Information</h4>
            <ul class="list-unstyled mb-0">
                <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span><i class="bi bi-person-fill text-primary me-3"></i><strong>Username</strong></span>
                    <span class="text-body-secondary"><?= esc($username ?? 'N/A') ?></span>
                </li>
                <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span><i class="bi bi-envelope-fill text-primary me-3"></i><strong>Email</strong></span>
                    <span class="text-body-secondary"><?= esc($email ?? 'N/A') ?></span>
                </li>
                <li class="d-flex justify-content-between align-items-center py-2">
                    <span><i class="bi bi-calendar-check-fill text-primary me-3"></i><strong>Member Since</strong></span>
                    <span class="text-body-secondary"><?= esc($member_since ? date('F d, Y', strtotime($member_since)) : 'N/A') ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const copyBtn = document.getElementById('copyPromptBtn');
        const promptText = document.getElementById('promptToCopy').innerText;
        
        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                navigator.clipboard.writeText(promptText).then(() => {
                    const originalIcon = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-check-lg"></i>';
                    this.classList.remove('bg-primary-subtle', 'text-primary-emphasis');
                    this.classList.add('bg-success-subtle', 'text-success-emphasis');
                    
                    setTimeout(() => {
                        this.innerHTML = originalIcon;
                        this.classList.remove('bg-success-subtle', 'text-success-emphasis');
                        this.classList.add('bg-primary-subtle', 'text-primary-emphasis');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            });
        }
    });
</script>
<?= $this->endSection() ?>
