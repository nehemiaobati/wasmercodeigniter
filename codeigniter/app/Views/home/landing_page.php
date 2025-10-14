<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    :root {
        --primary-color: #0d6efd;
        --secondary-color: #6c757d;
        --light-gray: #f8f9fa;
        --dark-bg: #1a1a2e;
        --light-bg: #ffffff;
        --text-dark: #343a40;
        --text-light: #f8f9fa;
    }

    /* Hero Section Styling */
    .hero-section {
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.9), rgba(26, 26, 46, 0.95));
        color: var(--text-light);
        padding: 100px 0;
        text-align: center;
        overflow: hidden;
        position: relative;
    }

    .hero-content {
        position: relative;
        z-index: 1;
    }

    .hero-section .display-3 {
        font-weight: 700;
        animation: fadeInDown 1s ease-out;
    }

    .hero-section .lead {
        font-size: 1.25rem;
        max-width: 700px;
        margin: 0 auto 30px;
        animation: fadeInUp 1s ease-out 0.5s;
        animation-fill-mode: both;
    }

    .hero-buttons .btn {
        animation: fadeInUp 1s ease-out 1s;
        animation-fill-mode: both;
    }

    /* Features Section Styling */
    .feature-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 4rem;
        height: 4rem;
        font-size: 2rem;
        border-radius: 50%;
        color: var(--light-bg);
        background-color: var(--primary-color);
        margin-bottom: 1.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }

    .feature-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 0;
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.15) !important;
    }

    /* New Custom Development Section Styling */
    .custom-dev-section {
        background-color: var(--light-bg);
        border-radius: 0.75rem;
    }

    .custom-dev-section .list-unstyled i {
        color: var(--primary-color);
        margin-right: 10px;
    }

    .process-step {
        display: flex;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }

    .process-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        background-color: #e7f1ff;
        border-radius: 50%;
        width: 45px;
        height: 45px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }


    /* How It Works Section Styling */
    .how-it-works {
        background-color: var(--light-bg);
    }

    .step-number {
        width: 60px;
        height: 60px;
        background-color: var(--light-gray);
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
        border-radius: 50%;
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Call-to-Action Section Styling */
    .cta-section {
        background-color: var(--dark-bg);
        color: var(--text-light);
        border-radius: 0.75rem;
    }

    /* Animations for engagement */
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
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
    <div class="container hero-content">
        <h1 class="display-3 mb-3"><?= esc($heroTitle ?? 'Unlock the Power of Digital Assets') ?></h1>
        <p class="lead"><?= esc($heroSubtitle ?? 'Your one-stop solution for real-time cryptocurrency data and cutting-edge AI insights. Seamless, fast, and reliable.') ?></p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center hero-buttons">
            <a href="<?= url_to('register') ?>" class="btn btn-primary btn-lg px-4 gap-3 fw-bold">Start for Free</a>
            <a href="#features" class="btn btn-outline-light btn-lg px-4">Explore Features</a>
        </div>
    </div>
</section>

<div class="container">
    <!-- Features Section -->
    <section id="features" class="py-5 my-5 text-center">
        <div class="fade-in-section">
            <h2 class="display-5 fw-bold mb-5">Our Core Services</h2>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm feature-card">
                        <div class="card-body p-4">
                            <div class="feature-icon"><i class="bi bi-robot"></i></div>
                            <h3 class="fs-4 fw-bold">Generative AI with Gemini</h3>
                            <p class="text-muted">Leverage Google's powerful Gemini API. Generate creative text, analyze data, or interact with a state-of-the-art AI, all within our platform.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm feature-card">
                        <div class="card-body p-4">
                            <div class="feature-icon"><i class="bi bi-search"></i></div>
                            <h3 class="fs-4 fw-bold">Real-Time Crypto Data</h3>
                            <p class="text-muted">Instantly query Bitcoin and Litecoin addresses for balance and transaction history. Get accurate, up-to-the-minute data directly from the blockchain.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm feature-card">
                        <div class="card-body p-4">
                            <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                            <h3 class="fs-4 fw-bold">Simple & Secure Payments</h3>
                            <p class="text-muted">Easily top up your account balance using our secure payment system. A pay-per-query model means you only pay for what you use.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- NEW: Custom Web Development Section -->
    <section id="custom-development" class="py-5 my-5 custom-dev-section">
        <div class="container fade-in-section">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Need a Tailored Solution?</h2>
                <p class="lead">Beyond our ready-to-use tools, we build bespoke web applications to solve your unique business challenges.</p>
            </div>
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <h3 class="fw-bold mb-4">What We Offer</h3>
                    <ul class="list-unstyled fs-5">
                        <li class="mb-3"><i class="bi bi-check2-circle"></i> Custom Web Applications</li>
                        <li class="mb-3"><i class="bi bi-check2-circle"></i> CodeIgniter & PHP Development</li>
                        <li class="mb-3"><i class="bi bi-check2-circle"></i> Third-Party API Integrations</li>
                        <li class="mb-3"><i class="bi bi-check2-circle"></i> Website Performance Optimization</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <h3 class="fw-bold mb-4">Our Proven Process</h3>
                    <div class="process-step">
                        <div class="process-number">1</div>
                        <div>
                            <h5 class="fw-bold">Consultation</h5>
                            <p class="text-muted">We start by understanding your vision, goals, and technical requirements in a free, no-obligation meeting.</p>
                        </div>
                    </div>
                    <div class="process-step">
                        <div class="process-number">2</div>
                        <div>
                            <h5 class="fw-bold">Proposal &amp; Planning</h5>
                            <p class="text-muted">You receive a detailed project proposal, including timeline, deliverables, and a transparent quote.</p>
                        </div>
                    </div>
                    <div class="process-step">
                        <div class="process-number">3</div>
                        <div>
                            <h5 class="fw-bold">Development &amp; Launch</h5>
                            <p class="text-muted">We build your application with clean, efficient code and deploy it to a secure, scalable server environment.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5">
                <a href="<?= url_to('contact.form') ?>" class="btn btn-primary btn-lg fw-bold">Book a Free Consultation</a>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works py-5 my-5">
        <div class="fade-in-section">
            <h2 class="display-5 fw-bold mb-5 text-center">Get Started in 3 Easy Steps</h2>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <h4 class="fw-bold">Create Account</h4>
                        <p class="text-muted">Sign up in seconds. All you need is a username and email to get started.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <h4 class="fw-bold">Add Funds</h4>
                        <p class="text-muted">Make a secure payment to add balance to your account. Our service is affordable and flexible.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <h4 class="fw-bold">Start Exploring</h4>
                        <p class="text-muted">Use your balance to access our Crypto and AI services instantly. No subscriptions needed.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section id="cta" class="py-5 mb-5 text-center cta-section">
        <div class="fade-in-section">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="display-5 fw-bold mb-3">Ready to Dive In?</h2>
                    <p class="lead mb-4">Join now and get instant access to powerful data tools. Your next big discovery is just a query away.</p>
                    <a href="<?= url_to('register') ?>" class="btn btn-primary btn-lg px-4 fw-bold">Create Your Account</a>
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