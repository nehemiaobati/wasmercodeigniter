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
        <span class="badge bg-primary-subtle text-primary mb-3 rounded-pill px-3 py-2 fw-bold">Powered by Ollama (Local AI)</span>
        <h1 class="display-3 fw-bold mb-4"><?= esc($heroTitle ?? 'Private, Local AI Workspace') ?></h1>
        <p class="lead mb-5 mx-auto text-muted" style="max-width: 800px;"><?= esc($heroSubtitle ?? 'Run powerful LLMs directly on your server. Private, secure, and always available.') ?></p>

        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
            <a href="<?= url_to('register') ?>" class="btn btn-primary btn-lg px-5 fw-bold rounded-pill shadow-sm">Get Started</a>
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
                                <i class="bi bi-cpu-fill fs-4"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-3">Local Inference</h3>
                        <p class="text-muted mb-0">Leverage models like Llama 3, Mistral, and Phi-3. All processing happens on your own hardware, ensuring maximum privacy and zero latency.</p>
                    </div>
                </div>
            </div>

            <!-- Memory System -->
            <div class="col-12 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-effect">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <span class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle" style="width: 3rem; height: 3rem;">
                                <i class="bi bi-memory fs-4"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-3">Hybrid Memory</h3>
                        <p class="text-muted mb-0">Advanced vector-semantic search combined with keyword tracking. Your AI learns from every interaction, building a personalized knowledge base.</p>
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
                        <h3 class="fw-bold mb-3">Privacy-First Documents</h3>
                        <p class="text-muted mb-0">Summarize and analyze documents locally. No data ever leaves your server. Perfect for sensitive legal and medical documentation.</p>
                    </div>
                </div>
            </div>

            <!-- Custom Prompts -->
            <div class="col-12 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-effect">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <span class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle" style="width: 3rem; height: 3rem;">
                                <i class="bi bi-command fs-4"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-3">Persona Control</h3>
                        <p class="text-muted mb-0">Define custom system prompts and personas. Fine-tune how the AI responds to match your specific workflow and tone.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 mb-5">
    <div class="container">
        <div class="card bg-primary text-white rounded-4 overflow-hidden shadow-lg border-0 hover-effect" style="background: linear-gradient(45deg, #1e3a8a, #3b82f6) !important;">
            <div class="card-body p-5 text-center position-relative">
                <h2 class="display-5 fw-bold mb-4">Ownership of Intelligence</h2>
                <p class="lead mb-4 opacity-75">Start your self-hosted AI journey today.</p>
                <a href="<?= url_to('register') ?>" class="btn btn-light btn-lg px-5 fw-bold rounded-pill text-primary">Join Now</a>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>