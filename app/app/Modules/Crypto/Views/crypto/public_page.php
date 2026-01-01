<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    /* Hero uses global gradient */
    .hero-section {
        background: var(--hero-gradient);
        color: var(--bs-white);
        padding: 4rem 0;
        position: relative;
        overflow: hidden;
    }

    /* Consistent Icon Wrapper */
    .feature-icon-wrapper {
        width: 3.5rem;
        height: 3.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        border-radius: 50%;
        background-color: var(--bs-primary);
        color: var(--bs-white);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
        transition: transform 0.3s ease;
    }

    .blueprint-card:hover .feature-icon-wrapper {
        transform: scale(1.1) rotate(-5deg);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Hero -->
<section class="hero-section text-center">
    <div class="container position-relative z-1">
        <h1 class="display-4 fw-bold mb-3">Instant Blockchain Insights</h1>
        <p class="lead opacity-75 col-lg-8 mx-auto">Get on-chain data for Bitcoin and Litecoin addresses without the complexity of a block explorer.</p>
        <a href="<?= url_to('register') ?>" class="btn btn-light btn-lg mt-4 fw-bold rounded-pill shadow-sm">Start Querying Now</a>
    </div>
</section>

<div class="container my-5">
    <div class="text-center mb-5">
        <span class="badge bg-primary-subtle text-primary mb-2 rounded-pill px-3 py-2">Services</span>
        <h2 class="fw-bold">Fast & Reliable Data</h2>
        <p class="lead text-body-secondary">Simple, fast, and accurate onscreen data.</p>
    </div>

    <!-- Features -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card blueprint-card h-100 border-0">
                <div class="card-body text-center p-4">
                    <div class="feature-icon-wrapper"><i class="bi bi-wallet2"></i></div>
                    <h5 class="card-title fw-bold">Real-Time Balance</h5>
                    <p class="card-text text-muted">Instantly retrieve the current confirmed balance for any public BTC or LTC address.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card blueprint-card h-100 border-0">
                <div class="card-body text-center p-4">
                    <div class="feature-icon-wrapper"><i class="bi bi-list-check"></i></div>
                    <h5 class="card-title fw-bold">Transaction History</h5>
                    <p class="card-text text-muted">Fetch a list of the latest transactions, including amounts, dates, and confirmation status.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card blueprint-card h-100 border-0">
                <div class="card-body text-center p-4">
                    <div class="feature-icon-wrapper"><i class="bi bi-piggy-bank"></i></div>
                    <h5 class="card-title fw-bold">Pay-As-You-Go</h5>
                    <p class="card-text text-muted">Simple and transparent pricing. Pay only for the queries you make. No monthly subscriptions.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Visualization -->
    <div class="card blueprint-card overflow-hidden my-5 border-0">
        <div class="row g-0 align-items-center">
            <div class="col-md-6">
                <!-- Lazy load -->
                <img src="https://images.pexels.com/photos/844124/pexels-photo-844124.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" class="img-fluid w-100 object-fit-cover h-100" style="min-height: 300px;" loading="lazy" alt="Blockchain Network">
            </div>
            <div class="col-md-6 p-5">
                <h3 class="fw-bold mb-3">Data at Your Fingertips</h3>
                <p class="text-muted mb-4">Our tool simplifies blockchain data access. Just enter an address, select your query, and get immediate results in a clean, easy-to-read format. Perfect for developers and traders.</p>
                <a href="<?= url_to('register') ?>" class="btn btn-outline-primary fw-bold rounded-pill px-4">Create Free Account</a>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="text-center py-5">
        <h4 class="fw-bold mb-3">Ready to Query?</h4>
        <p class="text-muted mb-4">Join thousands of users accessing real-time crypto data.</p>
        <a href="<?= url_to('register') ?>" class="btn btn-primary btn-lg px-5 fw-bold rounded-pill">Get Started</a>
    </div>
</div>
<?= $this->endSection() ?>