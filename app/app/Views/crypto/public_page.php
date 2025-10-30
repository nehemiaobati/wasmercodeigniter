<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .blueprint-card {
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
        border: none;
    }
    .hero-section {
        background-color: var(--light-bg);
        padding: 4rem 0;
    }
    .feature-icon {
        font-size: 2.5rem;
        color: var(--primary-color);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="hero-section text-center">
    <div class="container">
        <h1 class="display-4 fw-bold">Instant Blockchain Insights</h1>
        <p class="lead text-muted col-lg-8 mx-auto">Get on-chain data for Bitcoin and Litecoin addresses without the complexity of a block explorer.</p>
        <a href="<?= url_to('register') ?>" class="btn btn-primary btn-lg mt-3">Register to Use the Tool</a>
    </div>
</div>

<div class="container my-5">
    <div class="blueprint-header text-center mb-5">
        <h2 class="fw-bold">Core Features</h2>
        <p class="lead text-muted">Simple, fast, and accurate on-chain data.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card blueprint-card h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-wallet2 feature-icon mb-3"></i>
                    <h5 class="card-title fw-bold">Real-Time Balance Checks</h5>
                    <p class="card-text text-muted">Instantly retrieve the current balance for any public BTC or LTC address.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card blueprint-card h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-card-list feature-icon mb-3"></i>
                    <h5 class="card-title fw-bold">Detailed Transaction History</h5>
                    <p class="card-text text-muted">Fetch a list of the latest transactions, including amounts, dates, and confirmations.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card blueprint-card h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-cash-coin feature-icon mb-3"></i>
                    <h5 class="card-title fw-bold">Pay-As-You-Go Pricing</h5>
                    <p class="card-text text-muted">Simple and transparent pricing. Pay only for the queries you make with no monthly commitment.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row align-items-center my-5 py-5">
        <div class="col-md-6">
            <h3 class="fw-bold">Data at Your Fingertips</h3>
            <p class="text-muted">Our tool simplifies blockchain data access. Just enter an address, select your query, and get immediate results in a clean, easy-to-read format.</p>
        </div>
        <div class="col-md-6">
            <!-- <img src="https://placehold.co/800x500/e9ecef/6c757d?text=CryptoQuery+Results" class="img-fluid rounded shadow-sm" alt="Example results from the CryptoQuery tool showing a Bitcoin wallet balance."> -->
            <img src="https://images.pexels.com/photos/844124/pexels-photo-844124.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" class="img-fluid rounded shadow-sm" alt="Digital representation of a cryptocurrency blockchain network.">
        </div>
    </div>

    <div class="card blueprint-card bg-light text-center p-4">
        <div class="card-body">
            <h4 class="fw-bold">Start Querying the Blockchain</h4>
            <p class="text-muted">Create an account to get instant access to our powerful and affordable CryptoQuery tool.</p>
            <a href="<?= url_to('register') ?>" class="btn btn-primary">Create Your Account</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
