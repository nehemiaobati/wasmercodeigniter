<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    /* Hero uses global gradient but with reduced padding for inner pages */
    .hero-section {
        background: var(--hero-gradient);
        color: var(--bs-white);
        padding: 4rem 0;
        position: relative;
        overflow: hidden;
    }

    /* Icon wrapper for consistency with landing page */
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
        transform: scale(1.1) rotate(5deg);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="container position-relative z-1">
        <h1 class="display-4 fw-bold mb-3"><?= esc($heroTitle) ?></h1>
        <p class="lead opacity-75 col-lg-8 mx-auto"><?= esc($heroSubtitle) ?></p>
        <a href="<?= url_to('register') ?>" class="btn btn-light btn-lg mt-4 fw-bold rounded-pill shadow-sm">Sign Up to Get Started</a>
    </div>
</section>

<div class="container my-5">
    <div class="text-center mb-5">
        <span class="badge bg-primary-subtle text-primary mb-2 rounded-pill px-3 py-2">Features</span>
        <h2 class="fw-bold">Unlock Powerful Capabilities</h2>
        <p class="lead text-body-secondary">Streamline your workflow with advanced AI tools.</p>
    </div>

    <!-- Features Grid -->
    <div class="row g-4">
        <!-- Text Features -->
        <div class="col-md-4">
            <div class="card blueprint-card h-100 border-0">
                <div class="card-body text-center p-4">
                    <div class="feature-icon-wrapper"><i class="bi bi-file-earmark-text"></i></div>
                    <h5 class="card-title fw-bold">Document Analysis</h5>
                    <p class="card-text text-muted">Upload PDFs, text files, and more. Ask questions, get summaries, and extract key information in seconds.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card blueprint-card h-100 border-0">
                <div class="card-body text-center p-4">
                    <div class="feature-icon-wrapper"><i class="bi bi-pencil-square"></i></div>
                    <h5 class="card-title fw-bold">Creative Writing</h5>
                    <p class="card-text text-muted">Generate marketing copy, blog posts, emails, or even poetry. The AI Studio is your partner in creativity.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card blueprint-card h-100 border-0">
                <div class="card-body text-center p-4">
                    <div class="feature-icon-wrapper"><i class="bi bi-chat-dots"></i></div>
                    <h5 class="card-title fw-bold">Conversational Memory</h5>
                    <p class="card-text text-muted">The AI remembers previous parts of your conversation, allowing for complex, multi-step tasks and follow-up questions.</p>
                </div>
            </div>
        </div>

        <!-- Multimedia Features -->
        <div class="col-md-6">
            <div class="card blueprint-card h-100 border-0">
                <div class="card-body text-center p-4">
                    <div class="feature-icon-wrapper bg-gradient"><i class="bi bi-image"></i></div>
                    <h5 class="card-title fw-bold">Image Generation</h5>
                    <p class="card-text text-muted">Turn text into stunning visuals. Create unique artwork, marketing assets, and illustrations instantly.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card blueprint-card h-100 border-0">
                <div class="card-body text-center p-4">
                    <div class="feature-icon-wrapper bg-gradient"><i class="bi bi-camera-reels"></i></div>
                    <h5 class="card-title fw-bold">Video Synthesis</h5>
                    <p class="card-text text-muted">Create engaging videos from simple prompts. Bring your stories to life with AI-generated motion.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Visualization Section -->
    <div class="card blueprint-card overflow-hidden my-5 border-0">
        <div class="row g-0 align-items-center">
            <div class="col-md-6 p-5">
                <h3 class="fw-bold mb-3">Visualize Your Workflow</h3>
                <p class="text-muted mb-4">The intuitive interface makes it easy to manage your prompts, upload media, and interact with the AI. See your ideas come to life in a clean, organized workspace.</p>
                <div class="d-flex gap-2">
                    <span class="badge bg-body-secondary text-body-emphasis border">Easy Uploads</span>
                    <span class="badge bg-body-secondary text-body-emphasis border">Real-time Responses</span>
                </div>
            </div>
            <div class="col-md-6">
                <!-- Lazy load image for performance -->
                <img src="https://images.pexels.com/photos/8386440/pexels-photo-8386440.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" class="img-fluid w-100 object-fit-cover h-100" style="min-height: 300px;" loading="lazy" alt="AI Neural Network">
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="text-center py-5">
        <h4 class="fw-bold mb-3">Ready to Get Started?</h4>
        <p class="text-muted mb-4">Sign up today and experience the future of content creation and analysis.</p>
        <a href="<?= url_to('register') ?>" class="btn btn-primary btn-lg px-5 fw-bold rounded-pill">Create Your Account</a>
    </div>
</div>
<?= $this->endSection() ?>