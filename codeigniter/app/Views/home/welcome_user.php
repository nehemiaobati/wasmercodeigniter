<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    :root {
        --primary-accent: #0d6efd;
        --success-green: #198754;
        --warning-orange: #fd7e14;
        --light-bg: #f8f9fa;
        --card-bg: #ffffff;
        --card-border: #dee2e6;
        --text-muted: #6c757d;
    }

    .dashboard-header {
        margin-bottom: 2.5rem;
    }

    .dashboard-header h1 {
        font-weight: 700;
        color: var(--dark-gray);
    }
    
    .dashboard-header .lead {
        color: var(--text-muted);
    }

    .dashboard-card {
        background-color: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
        transition: all 0.3s ease-in-out;
        height: 100%;
    }
    
    .balance-card .card-body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    .balance-amount {
        font-size: 3rem;
        font-weight: 700;
        color: var(--success-green);
        line-height: 1.2;
    }
    
    .balance-card .text-muted {
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
    }

    .details-card .list-group-item {
        border-left: 0;
        border-right: 0;
        padding-left: 0;
        padding-right: 0;
    }

    .details-card .list-group-item:first-child {
        border-top: 0;
    }
    
    .details-card .list-group-item strong {
        display: inline-block;
        min-width: 120px;
    }
    
    .details-card .list-group-item i {
        color: var(--primary-accent);
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .action-card {
        display: flex;
        flex-direction: column;
        text-align: center;
        padding: 2rem;
    }
    
    .action-card .icon {
        font-size: 3rem;
        color: var(--primary-accent);
        margin-bottom: 1rem;
    }

    .action-card h4 {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .action-card p {
        flex-grow: 1;
        margin-bottom: 1.5rem;
    }

    .action-card .btn {
        font-weight: 600;
    }

    .low-balance-alert {
        background-color: #fff3cd;
        border-color: #ffeeba;
        color: #856404;
        border-radius: 0.5rem;
        padding: 1rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="dashboard-header text-center">
        <h1>Welcome back, <span class="text-primary"><?= esc($username ?? 'User') ?>!</span></h1>
        <p class="lead">Here's a quick overview of your account and available services.</p>
    </div>

    <div class="row g-4">
        <!-- Main Panel: Balance and Account Details -->
        <div class="col-lg-7">
            <div class="row g-4">
                <!-- Balance Card -->
                <div class="col-12">
                    <div class="dashboard-card balance-card">
                        <div class="card-body p-5">
                            <p class="text-muted text-uppercase fw-bold mb-2">Current Balance</p>
                            <div class="balance-amount">Ksh. <?= esc(number_format((float)($balance ?? 0), 2)) ?></div>
                            <p class="text-muted">Available for all services</p>
                            <a href="<?= url_to('payment.index') ?>" class="btn btn-success btn-lg mt-2 px-5">
                                <i class="bi bi-plus-circle"></i> Add Funds
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Account Details Card -->
                <div class="col-12">
                    <div class="dashboard-card details-card">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h4 class="fw-bold">Account Details</h4>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-person-fill"></i><strong>Username:</strong>
                                    <span class="ms-auto text-muted d-block text-truncate"><?= esc($username ?? 'N/A') ?></span>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-envelope-fill"></i><strong>Email:</strong>
                                    <span class="ms-auto text-muted d-block text-truncate"><?= esc($email ?? 'N/A') ?></span>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-calendar-check-fill"></i><strong>Member Since:</strong>
                                    <span class="ms-auto text-muted d-block text-truncate"><?= esc($member_since ? date('F d, Y', strtotime($member_since)) : 'N/A') ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Side Panel: Services -->
        <div class="col-lg-5">
            <div class="row g-4">
                <!-- Low Balance Alert -->
                <?php if (isset($balance) && (float)$balance < 100): ?>
                <div class="col-12">
                    <div class="low-balance-alert d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                        <div>
                            <h5 class="fw-bold">Low Balance</h5>
                            Your balance is running low. Add funds to ensure uninterrupted access to services.
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Crypto Service Card -->
                <div class="col-12">
                    <div class="dashboard-card action-card">
                        <div class="icon"><i class="bi bi-search"></i></div>
                        <h4>Crypto Data Service</h4>
                        <p class="text-muted">Query real-time balance and transaction data for Bitcoin and Litecoin addresses.</p>
                        <a href="<?= url_to('crypto.index') ?>" class="btn btn-outline-primary">Access Service</a>
                    </div>
                </div>

                <!-- Gemini API Card -->
                <div class="col-12">
                    <div class="dashboard-card action-card">
                        <div class="icon"><i class="bi bi-robot"></i></div>
                        <h4>Gemini AI</h4>
                        <p class="text-muted">Engage with Google's advanced AI. Generate content, analyze information, and more.</p>
                        <a href="<?= url_to('gemini.index') ?>" class="btn btn-outline-primary">Access AI</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
