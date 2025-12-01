<?= '
' ?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    /* MODIFICATION: Removed local variables and hardcoded colors. 
       All theming is now handled by global CSS variables and Bootstrap utilities. */

    /* Hero Section Styling (Remains Unchanged - Uses its own theme) */
    .hero-section {
        background: var(--hero-gradient);
        color: var(--bs-white);
        /* Using Bootstrap white for text */
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
        color: var(--feature-icon-color);
        background-color: var(--primary-color);
        margin-bottom: 1.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
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

    /* MODIFICATION: Uses Bootstrap theme-aware classes instead of hardcoded colors */
    .process-number,
    .step-number {
        font-size: 1.5rem;
        font-weight: 700;
        border-radius: 50%;
        width: 45px;
        height: 45px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }

    .step-number {
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem;
    }

    /* Call-to-Action Section Styling (Remains Unchanged - Intentionally Dark) */
    .cta-section {
        background-color: var(--cta-bg);
        color: var(--bs-white);
        /* Using Bootstrap white for text */
        border-radius: 0.75rem;
    }

    /* Animations */
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
        <h1 class="display-3 mb-3">The Ultimate Productivity Suite for Creators & Businesses.</h1>
        <p class="lead">Chat with a powerful AI assistant and Unlock on-chain insights for any BTC or LTC address. Top up your account easily with <span style="color: green;">M-Pesa</span>, <span style="color: red;"> Airtel Money</span>, and or Card. Simple, pay-as-you-go pricing.</p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center hero-buttons">
            <a href="<?= url_to('register') ?>" class="btn btn-primary btn-lg px-4 gap-3 fw-bold">Create Your Free Account</a>
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
                    <!-- MODIFICATION: Replaced custom 'feature-card' with standard 'blueprint-card' -->
                    <div class="card h-100 blueprint-card">
                        <div class="card-body p-4">
                            <div class="feature-icon"><i class="bi bi-robot"></i></div>
                            <h3 class="fs-4 fw-bold">Your Creative AI Co-Pilot <br><small class="text-muted fs-6">Powered by Gemini</small></h3>
                            <p class="text-muted">From writing marketing copy and <strong>generating images</strong> to analyzing documents, our AI assistant, powered by Google's Gemini, helps you work smarter.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 blueprint-card">
                        <div class="card-body p-4">
                            <div class="feature-icon"><i class="bi bi-search"></i></div>
                            <h3 class="fs-4 fw-bold">Instant Blockchain Insights</h3>
                            <p class="text-muted">Ditch the block explorers. Get real-time balance and transaction history for any Bitcoin or Litecoin address with a single click.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 blueprint-card">
                        <div class="card-body p-4">
                            <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                            <h3 class="fs-4 fw-bold">Pay Your Way. Pay-As-You-Go.</h3>
                            <p class="text-muted">Top up securely with <span style="color: green;">M-Pesa</span>, <span style="color: red;">Airtel Money</span>, or Card. You only pay for what you use—no subscriptions, no hidden fees.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MODIFICATION: Replaced custom 'custom-dev-section' with standard 'blueprint-card' -->
    <section id="custom-development" class="py-5 my-5 blueprint-card">
        <div class="container fade-in-section">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Need a Tailored Solution?</h2>
                <p class="lead text-body-secondary">Beyond our ready-to-use tools, we build bespoke web applications to solve your unique business challenges.</p>
            </div>
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <h3 class="fw-bold mb-4">What We Offer</h3>
                    <ul class="list-unstyled fs-5">
                        <li class="mb-3"><i class="bi bi-check2-circle"></i> Custom Web Applications</li>
                        <li class="mb-3"><i class="bi bi-check2-circle"></i> CodeIgniter & PHP Development</li>
                        <li class="mb-3"><i class="bi bi-check2-circle"></i> Third-Party Expert Service Integrations</li>
                        <li class="mb-3"><i class="bi bi-check2-circle"></i> Website Performance Optimization</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <h3 class="fw-bold mb-4">Our Proven Process</h3>
                    <div class="process-step">
                        <!-- MODIFICATION: Added theme-aware Bootstrap classes -->
                        <div class="process-number bg-primary-subtle text-primary-emphasis">1</div>
                        <div>
                            <h5 class="fw-bold">Consultation</h5>
                            <p class="text-body-secondary">We start by understanding your vision, goals, and technical requirements in a free, no-obligation meeting.</p>
                        </div>
                    </div>
                    <div class="process-step">
                        <div class="process-number bg-primary-subtle text-primary-emphasis">2</div>
                        <div>
                            <h5 class="fw-bold">Proposal & Planning</h5>
                            <p class="text-body-secondary">You receive a detailed project proposal, including timeline, deliverables, and a transparent quote.</p>
                        </div>
                    </div>
                    <div class="process-step">
                        <div class="process-number bg-primary-subtle text-primary-emphasis">3</div>
                        <div>
                            <h5 class="fw-bold">Development & Launch</h5>
                            <p class="text-body-secondary">We build your application with clean, efficient code and deploy it to a secure, scalable server environment.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5">
                <a href="<?= url_to('contact.form') ?>" class="btn btn-primary btn-lg fw-bold" aria-label="Book a free consultation">Book a Free Consultation</a>
            </div>
        </div>
    </section>

    <!-- Case Studies Section -->
    <section id="case-studies" class="py-5 my-5">
        <div class="container fade-in-section">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">What You Can Build</h2>
                <p class="lead text-body-secondary">See how our platform leverages AI to solve real-world problems.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="mb-3 text-primary"><i class="bi bi-file-earmark-text fs-1"></i></div>
                            <h4 class="card-title fw-bold">Legal Drafting</h4>
                            <p class="card-text text-muted">Generate professional legal letters and contracts in seconds using our advanced AI assistant. Perfect for small businesses.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="mb-3 text-primary"><i class="bi bi-journal-richtext fs-1"></i></div>
                            <h4 class="card-title fw-bold">Document Summarization</h4>
                            <p class="card-text text-muted">Upload lengthy PDF reports and get concise, actionable summaries. Save hours of reading time.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="mb-3 text-primary"><i class="bi bi-translate fs-1"></i></div>
                            <h4 class="card-title fw-bold">Content Localization</h4>
                            <p class="card-text text-muted">Translate and adapt marketing copy for different African regions, maintaining cultural relevance and tone.</p>
                        </div>
                    </div>
                </div>
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
                        <!-- MODIFICATION: Added theme-aware Bootstrap classes and border -->
                        <div class="step-number bg-primary-subtle text-primary-emphasis border border-primary">1</div>
                        <h4 class="fw-bold">Create Account</h4>
                        <p class="text-body-secondary">Sign up in seconds. We'll gift you <strong>Ksh. 30</strong> in starter credits to begin exploring immediately.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-number bg-primary-subtle text-primary-emphasis border border-primary">2</div>
                        <h4 class="fw-bold">Add Funds</h4>
                        <p class="text-body-secondary">Make a secure payment to add balance to your account. Our service is affordable and flexible.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-number bg-primary-subtle text-primary-emphasis border border-primary">3</div>
                        <h4 class="fw-bold">Start Exploring</h4>
                        <p class="text-body-secondary">Use your balance to access our Crypto and AI services instantly. No subscriptions needed.</p>
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
                    <h2 class="display-5 fw-bold mb-3">Ready to Build, Create, and Discover?</h2>
                    <p class="lead mb-4">Your account is free. Your first few queries are on us. Let's get started.</p>
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