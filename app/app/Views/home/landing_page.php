<?= '
' ?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    /* MODIFICATION: Removed local variables and hardcoded colors. 
       All theming is now handled by global CSS variables and Bootstrap utilities. */

    /* Hero Section - Uses Global Variables */
    .hero-section {
        background: var(--hero-gradient);
        color: var(--bs-white);
        padding: 5rem 0;
        /* 100px -> 5rem */
        text-align: center;
        overflow: hidden;
        position: relative;
    }

    /* Feature Icons */
    .feature-icon-wrapper {
        width: 4rem;
        height: 4rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        border-radius: 50%;
        background-color: var(--bs-primary);
        color: var(--bs-white);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
        transition: transform 0.3s ease;
    }

    .blueprint-card:hover .feature-icon-wrapper {
        transform: scale(1.1) rotate(5deg);
    }

    /* Animation Utilities */
    .fade-in-up {
        animation: fadeInUp 0.8s ease-out both;
    }

    .delay-100 {
        animation-delay: 0.1s;
    }

    .delay-200 {
        animation-delay: 0.2s;
    }

    .delay-300 {
        animation-delay: 0.3s;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-in-section {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s ease-out, transform 0.6s ease-out;
    }

    .fade-in-section.is-visible {
        opacity: 1;
        transform: translateY(0);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>


<!-- Hero Section -->
<section class="hero-section">
    <div class="container position-relative z-1">
        <h1 class="display-3 fw-bold mb-4 fade-in-up">The Ultimate Productivity Suite<br>for <span class="text-white-50">Creators</span> & <span class="text-white-50">Businesses</span>.</h1>
        <p class="lead mb-4 mx-auto fade-in-up delay-100" style="max-width: 700px;">Chat with a powerful AI assistant and Unlock on-chain insights for any BTC or LTC address. Top up your account easily with <span class="text-success fw-bold">M-Pesa</span>, <span class="text-danger fw-bold">Airtel Money</span>, or Card.</p>
        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center fade-in-up delay-200">
            <a href="<?= url_to('register') ?>" class="btn btn-light btn-lg px-5 fw-bold rounded-pill shadow-lg">Create Free Account</a>
            <a href="#features" class="btn btn-outline-light btn-lg px-5 rounded-pill">Explore Features</a>
        </div>
    </div>
</section>

<div class="container">
    <!-- Features Section -->
    <section id="features" class="py-5 my-5 text-center">
        <div class="fade-in-section">
            <div class="mb-5">
                <span class="badge bg-primary-subtle text-primary mb-2 rounded-pill px-3 py-2">Our Services</span>
                <h2 class="display-5 fw-bold">What We Offer</h2>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 blueprint-card border-0">
                        <div class="card-body p-4">
                            <div class="feature-icon-wrapper"><i class="bi bi-stars"></i></div>
                            <h3 class="fs-4 fw-bold mb-3">Creative AI Co-Pilot</h3>
                            <p class="text-muted mb-0">From writing marketing copy and <strong>generating images</strong> to analyzing documents, our AI assistant (Gemini 1.5) helps you work smarter.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 blueprint-card border-0">
                        <div class="card-body p-4">
                            <div class="feature-icon-wrapper"><i class="bi bi-graph-up-arrow"></i></div>
                            <h3 class="fs-4 fw-bold mb-3">Blockchain Insights</h3>
                            <p class="text-muted mb-0">Real-time balance and transaction history for any Bitcoin or Litecoin address at your fingertips. No more complex block explorers.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 blueprint-card border-0">
                        <div class="card-body p-4">
                            <div class="feature-icon-wrapper"><i class="bi bi-wallet2"></i></div>
                            <h3 class="fs-4 fw-bold mb-3">Pay-As-You-Go</h3>
                            <p class="text-muted mb-0">Secure top-ups with <span class="text-success fw-bold">M-Pesa</span>, <span class="text-danger fw-bold">Airtel</span>, or Card. No subscriptions, no hidden fees. Just pay for what you use.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bespoke Development Section -->
    <section id="custom-development" class="py-5 my-5">
        <div class="container fade-in-section">
            <div class="card w-100 blueprint-card overflow-hidden">
                <div class="row g-0 align-items-center">
                    <div class="col-lg-6 p-5">
                        <span class="badge bg-info-subtle text-info-emphasis mb-2 rounded-pill px-3">Enterprise</span>
                        <h2 class="display-6 fw-bold mb-4">Need a Tailored Solution?</h2>
                        <p class="lead text-body-secondary mb-4">We build bespoke web applications to solve your unique business challenges using CodeIgniter and modern web standards.</p>

                        <ul class="list-unstyled fs-5 mb-4">
                            <li class="mb-2 d-flex align-items-center"><i class="bi bi-check-circle-fill text-primary me-3"></i> Custom Web Apps</li>
                            <li class="mb-2 d-flex align-items-center"><i class="bi bi-check-circle-fill text-primary me-3"></i> API Integrations</li>
                            <li class="mb-2 d-flex align-items-center"><i class="bi bi-check-circle-fill text-primary me-3"></i> Performance Tuning</li>
                        </ul>

                        <a href="<?= url_to('contact.form') ?>" class="btn btn-primary btn-lg rounded-pill fw-bold">Book Consultation</a>
                    </div>
                    <div class="col-lg-6 bg-body-tertiary p-5 h-100">
                        <h4 class="fw-bold mb-4">Our Process</h4>
                        <!-- Process Step 1 -->
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <span class="d-inline-flex align-items-center justify-content-center bg-primary text-white fs-5 fw-bold rounded-circle" style="width: 48px; height: 48px;">1</span>
                            </div>
                            <div class="ms-3">
                                <h5 class="fw-bold">Consultation</h5>
                                <p class="text-body-secondary mb-0">We listen to your vision and requirements in a free discovery meeting.</p>
                            </div>
                        </div>
                        <!-- Process Step 2 -->
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <span class="d-inline-flex align-items-center justify-content-center bg-primary text-white fs-5 fw-bold rounded-circle" style="width: 48px; height: 48px;">2</span>
                            </div>
                            <div class="ms-3">
                                <h5 class="fw-bold">Planning</h5>
                                <p class="text-body-secondary mb-0">You get a detailed roadmap, timeline, and transparent pricing.</p>
                            </div>
                        </div>
                        <!-- Process Step 3 -->
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <span class="d-inline-flex align-items-center justify-content-center bg-primary text-white fs-5 fw-bold rounded-circle" style="width: 48px; height: 48px;">3</span>
                            </div>
                            <div class="ms-3">
                                <h5 class="fw-bold">Build & Launch</h5>
                                <p class="text-body-secondary mb-0">We develop with clean code and deploy to your secure server.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Case Studies Section -->
    <section id="case-studies" class="py-5 my-5">
        <div class="container fade-in-section">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Use Cases</h2>
                <p class="lead text-body-secondary">Real-world applications of our technology.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm blueprint-card">
                        <div class="card-body p-4">
                            <div class="mb-3 text-primary"><i class="bi bi-code-square fs-1"></i></div>
                            <h4 class="card-title fw-bold">Code Generation</h4>
                            <p class="card-text text-body-secondary">Generate boilerplate, debug functions, and write scripts in seconds.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm blueprint-card">
                        <div class="card-body p-4">
                            <div class="mb-3 text-primary"><i class="bi bi-file-earmark-pdf fs-1"></i></div>
                            <h4 class="card-title fw-bold">Summarization</h4>
                            <p class="card-text text-body-secondary">Turn long PDFs into concise summaries.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm blueprint-card">
                        <div class="card-body p-4">
                            <div class="mb-3 text-primary"><i class="bi bi-graph-up fs-1"></i></div>
                            <h4 class="card-title fw-bold">Market Insights</h4>
                            <p class="card-text text-body-secondary">Analyze wallet trends and transaction patterns instantly.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section id="cta" class="py-5 mb-5 text-center">
        <div class="fade-in-section">
            <div class="card bg-dark text-white rounded-4 overflow-hidden shadow-lg p-5 border-0" style="background: var(--hero-gradient) !important;">
                <div class="row justify-content-center position-relative z-1">
                    <div class="col-lg-8">
                        <h2 class="display-5 fw-bold mb-3">Ready to Start?</h2>
                        <p class="lead mb-4 text-white-50">Join thousands of users building efficiently with AFRIKENKID.</p>
                        <a href="<?= url_to('register') ?>" class="btn btn-light btn-lg px-5 fw-bold rounded-pill">Get Started</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Simple fade-in animation on scroll for sections
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });

        const sections = document.querySelectorAll('.fade-in-section');
        sections.forEach(section => {
            observer.observe(section);
        });
    });
</script>
<?= $this->endSection() ?>