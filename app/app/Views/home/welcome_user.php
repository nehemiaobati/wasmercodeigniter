<?= '
' ?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    :root {
        --primary-accent: #0d6efd;
        --success-green: #198754;
        --text-muted: #6c757d;
        --text-dark: #212529;
    }

    /* --- Page Specific Enhancements --- */
    .dashboard-header h1 {
        font-weight: 700;
    }
    .card-body {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }
    .card-title {
        font-weight: 600;
        color: var(--text-dark);
    }
    .card-text {
        color: var(--text-muted);
        flex-grow: 1;
    }
    .icon {
        font-size: 2.5rem;
        color: var(--primary-accent);
        margin-bottom: 1rem;
    }
    .tip-box {
        background-color: #e7f1ff;
        border-left: 5px solid var(--primary-accent);
        border-radius: 0.5rem;
        padding: 1.5rem;
    }
    .tip-box .tip-title {
        font-weight: 600;
        color: var(--primary-accent);
    }
    .balance-amount {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--success-green);
        line-height: 1;
    }
    .prompt-suggestion {
        background-color: var(--light-bg);
        border: 1px dashed var(--card-border);
        border-radius: 0.5rem;
        padding: 1rem;
        font-size: 0.9rem;
        margin-top: 1.5rem;
        position: relative;
    }
    .prompt-suggestion small {
        font-weight: 500;
        color: var(--text-muted);
    }
    .prompt-suggestion .copy-btn {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        cursor: pointer;
    }
    .account-info-section ul li {
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--light-bg);
    }
    .account-info-section ul li:last-child {
        border-bottom: none;
    }
    .account-info-section i {
        color: var(--primary-accent);
        margin-right: 1rem;
    }
    .low-balance-alert {
        background-color: #fff3cd;
        border-color: #ffeeba;
        color: #856404;
        border-radius: 0.5rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    // Educative content: an array of tips to be displayed randomly
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

    <!-- Low Balance Alert (if applicable) -->
    <?php if (isset($balance) && (float)$balance < 50): ?>
    <div class="alert low-balance-alert d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
        <div>
            <h5 class="alert-heading fw-bold">Your Balance is Low!</h5>
            <p class="mb-0">Don't let your creative flow be interrupted. Top up your account to continue using our services without a hitch.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="blueprint-header text-center mt-4">
        <h1>Welcome back, <span class="text-primary"><?= esc($username ?? 'User') ?>!</span></h1>
        <p class="lead text-muted">Your digital toolkit is ready. What will you create today?</p>
    </div>

    <!-- Tip of the Day -->
    <div class="tip-box mb-5">
        <p class="tip-title mb-1"><i class="bi bi-lightbulb-fill"></i> Pro Tip:</p>
        <p class="mb-0"><?= esc(str_replace('**', '', $randomTip)) // Simple formatting for display ?></p>
    </div>

    <!-- Scalable 3-Column Grid for Services -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">

        <!-- AI Studio Card -->
        <div class="col">
            <div class="card blueprint-card h-100">
                <div class="card-body p-4">
                    <div class="icon"><i class="bi bi-stars"></i></div>
                    <h4 class="card-title">AI Studio</h4>
                    <p class="card-text">Your creative co-pilot for writing, analysis, and brainstorming. Powered by Google's Gemini.</p>
                    <div class="prompt-suggestion">
                        <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill copy-btn" id="copyPromptBtn" title="Copy prompt">
                            <i class="bi bi-clipboard"></i>
                        </span>
                        <small class="d-block">Try this prompt:</small>
                        <p class="mb-0" id="promptToCopy">"Write a short, engaging marketing email for a new coffee shop opening in Nairobi."</p>
                    </div>
                    <a href="<?= url_to('gemini.index') ?>" class="btn btn-primary mt-4">Launch AI Studio <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>

        <!-- CryptoQuery Card -->
        <div class="col">
            <div class="card blueprint-card h-100">
                <div class="card-body p-4">
                    <div class="icon"><i class="bi bi-search"></i></div>
                    <h4 class="card-title">CryptoQuery</h4>
                    <p class="card-text">Get instant, real-time balance and transaction history for any public Bitcoin or Litecoin address.</p>
                    <a href="<?= url_to('crypto.index') ?>" class="btn btn-primary mt-auto">Run a Query <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Card -->
        <div class="col">
            <div class="card blueprint-card h-100">
                <div class="card-body p-4">
                    <div class="icon"><i class="bi bi-wallet2"></i></div>
                    <h4 class="card-title">Quick Actions</h4>
                    <div class="text-center my-3">
                        <p class="text-muted text-uppercase small mb-1">Current Balance</p>
                        <div class="balance-amount">Ksh. <?= esc(number_format((float)($balance ?? 0), 2)) ?></div>
                    </div>
                    <div class="d-grid gap-2 mt-auto">
                        <a href="<?= url_to('payment.index') ?>" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add Funds</a>
                        <a href="<?= url_to('account.index') ?>" class="btn btn-outline-secondary"><i class="bi bi-receipt"></i> View History</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Minimal Account Info Section -->
    <div class="card blueprint-card mt-5">
        <div class="card-body p-4 account-info-section">
            <h4 class="fw-bold mb-3 text-center">Account Information</h4>
            <ul class="list-unstyled mb-0">
                <li class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-fill"></i><strong>Username</strong></span>
                    <span class="text-muted"><?= esc($username ?? 'N/A') ?></span>
                </li>
                <li class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-envelope-fill"></i><strong>Email</strong></span>
                    <span class="text-muted"><?= esc($email ?? 'N/A') ?></span>
                </li>
                <li class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-check-fill"></i><strong>Member Since</strong></span>
                    <span class="text-muted"><?= esc($member_since ? date('F d, Y', strtotime($member_since)) : 'N/A') ?></span>
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