<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    /* MODIFICATION: Removed blueprint-card styles as they are now global. */
    .hero-section {
        /* MODIFICATION: Switched to use theme variable for background. */
        background-color: var(--card-bg); 
        padding: 4rem 0;
        border-bottom: 1px solid var(--border-color);
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
        <h1 class="display-4 fw-bold"><?= esc($heroTitle) ?></h1>
        <p class="lead text-muted col-lg-8 mx-auto"><?= esc($heroSubtitle) ?></p>
        <a href="<?= url_to('register') ?>" class="btn btn-primary btn-lg mt-3">Sign Up to Get Started</a>
    </div>
</div>

<div class="container my-5">
    <div class="blueprint-header text-center mb-5">
        <h2 class="fw-bold">Key Features</h2>
        <p class="lead text-muted">Unlock powerful capabilities to streamline your workflow.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card blueprint-card h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-file-earmark-text feature-icon mb-3"></i>
                    <h5 class="card-title fw-bold">Document Analysis</h5>
                    <p class="card-text text-muted">Upload PDFs, text files, and more. Ask questions, get summaries, and extract key information in seconds.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card blueprint-card h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-pencil-square feature-icon mb-3"></i>
                    <h5 class="card-title fw-bold">Creative Writing</h5>
                    <p class="card-text text-muted">Generate marketing copy, blog posts, emails, or even poetry. The AI Studio is your partner in creativity.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card blueprint-card h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-arrow-repeat feature-icon mb-3"></i>
                    <h5 class="card-title fw-bold">Conversational Memory</h5>
                    <p class="card-text text-muted">The AI remembers previous parts of your conversation, allowing for complex, multi-step tasks and follow-up questions.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row align-items-center my-5 py-5">
        <div class="col-md-6">
            <h3 class="fw-bold">Visualize Your Workflow</h3>
            <p class="text-muted">The intuitive interface makes it easy to manage your prompts, upload media, and interact with the AI. See your ideas come to life in a clean, organized workspace.</p>
        </div>
        <div class="col-md-6">
            <img src="https://images.pexels.com/photos/8386440/pexels-photo-8386440.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" class="img-fluid rounded shadow-sm" alt="Abstract visualization of an artificial intelligence neural network.">
        </div>
    </div>

    <!-- MODIFICATION: Removed 'bg-light' and ensured 'blueprint-card' is used for theme compatibility. -->
    <div class="card blueprint-card text-center p-4">
        <div class="card-body">
            <h4 class="fw-bold">Ready to Get Started?</h4>
            <p class="text-muted">Sign up today and experience the future of content creation and analysis.</p>
            <a href="<?= url_to('register') ?>" class="btn btn-primary">Create Your Account</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>