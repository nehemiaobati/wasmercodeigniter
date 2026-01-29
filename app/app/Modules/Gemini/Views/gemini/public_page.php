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
        <span class="badge bg-primary-subtle text-primary mb-3 rounded-pill px-3 py-2 fw-bold">Powered by Gemini</span>
        <h1 class="display-3 fw-bold mb-4"><?= esc($heroTitle ?? 'Enterprise AI Solutions') ?></h1>
        <p class="lead mb-5 mx-auto text-muted" style="max-width: 800px;"><?= esc($heroSubtitle ?? 'Unlock the power of generative AI for content, images, and data analysis.') ?></p>

        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
            <a href="<?= url_to('register') ?>" class="btn btn-primary btn-lg px-5 fw-bold rounded-pill shadow-sm">Start Creating Free</a>
            <a href="#capabilities" class="btn btn-outline-secondary btn-lg px-5 rounded-pill">View Capabilities</a>
        </div>
    </div>
</section>

<!-- Feature Grid -->
<section id="capabilities" class="py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Text Generation -->
            <div class="col-12 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-effect">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <span class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle" style="width: 3rem; height: 3rem;">
                                <i class="bi bi-chat-square-text-fill fs-4"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-3">Intelligent Writing</h3>
                        <p class="text-muted mb-0">Generate marketing copy, blog posts, and technical documentation with context-aware AI. Perfect for scaling your content strategy.</p>
                    </div>
                </div>
            </div>

            <!-- Image Generation -->
            <div class="col-12 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-effect">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <span class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle" style="width: 3rem; height: 3rem;">
                                <i class="bi bi-images fs-4"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-3">Image Synthesis</h3>
                        <p class="text-muted mb-0">Turn text descriptions into stunning, high-resolution images. Create assets for ads, social media, and web design instantly.</p>
                    </div>
                </div>
            </div>

            <!-- Document Ops -->
            <div class="col-12 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-effect">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <span class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle" style="width: 3rem; height: 3rem;">
                                <i class="bi bi-file-earmark-pdf-fill fs-4"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-3">Document Analysis</h3>
                        <p class="text-muted mb-0">Upload PDFs and get instant summaries, key insights, and answers to complex questions. Turn heavy documentation into actionable data.</p>
                    </div>
                </div>
            </div>

            <!-- Voice & Video -->
            <div class="col-12 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-effect">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <span class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle" style="width: 3rem; height: 3rem;">
                                <i class="bi bi-mic-fill fs-4"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-3">Voice & Video</h3>
                        <p class="text-muted mb-0">Synthesize natural-sounding speech and generate short video clips from text prompts. Bring your stories to life with multimedia.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 mb-5">
    <div class="container">
        <div class="card bg-primary text-white rounded-4 overflow-hidden shadow-lg border-0 hover-effect" style="background: var(--hero-gradient, linear-gradient(45deg, #0d6efd, #0a58ca)) !important;">
            <div class="card-body p-5 text-center position-relative">
                <h2 class="display-5 fw-bold mb-4">Ready to Innovate?</h2>
                <p class="lead mb-4 opacity-75">Join thousands of creators using our AI platform.</p>
                <a href="<?= url_to('register') ?>" class="btn btn-primary btn-lg px-5 fw-bold rounded-pill">Get Started Now</a>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>