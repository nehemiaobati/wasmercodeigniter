<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<style>
    section[id] {
        scroll-margin-top: 5rem;
    }

    .hover-effect {
        transition: transform 0.2s ease-in-out;
    }

    .hover-effect:hover {
        transform: translateY(-5px);
    }
</style>
<!-- Hero Section -->
<section class="py-5 bg-body-tertiary">
    <div class="container py-5 text-center">
        <span class="badge bg-warning-subtle text-warning-emphasis mb-3 rounded-pill px-3 py-2 fw-bold">Live Data Truth</span>
        <h1 class="display-3 fw-bold mb-4">Blockchain Audit & Verification Tools</h1>
        <p class="lead mb-5 mx-auto text-muted" style="max-width: 700px;">Ensure your business records are immutable and verifiable. Real-time transparency for Bitcoin and Litecoin. 100% Anonymous.</p>

        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
            <a href="<?= url_to('register') ?>" class="btn btn-warning btn-lg px-5 fw-bold rounded-pill shadow-sm text-dark">Start Audit Free</a>
            <a href="#features" class="btn btn-outline-secondary btn-lg px-5 rounded-pill">View Audit Trail</a>
        </div>
    </div>
</section>

<!-- Live Data Features -->
<section id="features" class="py-5">
    <div class="container">
        <div class="row g-4 align-items-center">
            <!-- Feature 1 -->
            <div class="col-12 col-md-6 order-md-2">
                <div class="p-4 p-lg-5 bg-body-tertiary rounded-4 border hover-effect">
                    <!-- Mock UI -->
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning rounded-circle p-2 me-3"><i class="bi bi-shield-lock-fill text-white fs-4"></i></div>
                        <div>
                            <div class="fw-bold">Data Provenance</div>
                            <div class="small text-muted">Hash: 8a...2f9c</div>
                        </div>
                        <div class="ms-auto fw-bold text-success">Verified</div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="bg-secondary rounded-circle p-2 me-3"><i class="bi bi-clock-history text-white fs-4"></i></div>
                        <div>
                            <div class="fw-bold">Timestamp Audit</div>
                            <div class="small text-muted">Confirmed on-chain</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 order-md-1">
                <div class="pe-md-5">
                    <h2 class="display-6 fw-bold mb-3">Immutable Verification</h2>
                    <p class="lead text-muted mb-4">Bridge the trust gap with cryptographic proof. Verify transaction integrity and maintain an audit trail that cannot be tampered with.</p>
                    <ul class="list-unstyled">
                        <li class="mb-3 d-flex align-items-center"><i class="bi bi-check-circle-fill text-success me-3 fs-5"></i> <strong>Data Provenance:</strong> Confirm origin of funds/data.</li>
                        <li class="mb-3 d-flex align-items-center"><i class="bi bi-check-circle-fill text-success me-3 fs-5"></i> <strong>Accountability:</strong> Traceable audit trails.</li>
                        <li class="mb-3 d-flex align-items-center"><i class="bi bi-check-circle-fill text-success me-3 fs-5"></i> <strong>Integrity:</strong> Cryptographic tamper-proofing.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Supported Currencies -->
<section class="py-5 bg-body-tertiary border-top border-bottom">
    <div class="container text-center">
        <h3 class="fw-bold mb-5">Supported Networks</h3>
        <div class="row g-4 justify-content-center">
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm py-4 hover-effect">
                    <div class="card-body">
                        <i class="bi bi-currency-bitcoin text-warning display-4 mb-3 d-block"></i>
                        <h5 class="fw-bold mb-0">Bitcoin</h5>
                        <p class="small text-muted">Mainnet</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm py-4 hover-effect">
                    <div class="card-body">
                        <i class="bi bi-currency-exchange text-secondary display-4 mb-3 d-block"></i>
                        <h5 class="fw-bold mb-0">Litecoin</h5>
                        <p class="small text-muted">Scrypt</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="py-5 mb-5">
    <div class="container text-center">
        <div class="card bg-body-tertiary p-5 border-0 shadow-sm hover-effect rounded-4">
            <h2 class="fw-bold mb-3">Start Analyzing the Blockchain</h2>
            <p class="text-body-secondary mb-4">Create an account to save your favorite wallets and enable alerts.</p>
            <a href="<?= url_to('register') ?>" class="btn btn-primary btn-lg px-5 rounded-pill fw-bold">Create Free Account</a>
        </div>
    </div>
</section>
<?= $this->endSection() ?>