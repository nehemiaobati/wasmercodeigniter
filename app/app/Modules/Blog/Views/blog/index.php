<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="text-center mb-5">
        <span class="badge bg-primary-subtle text-primary mb-2 rounded-pill px-3 py-2">Blog</span>
        <h1 class="display-4 fw-bold">Tech Insights & Tutorials</h1>
        <p class="lead text-body-secondary col-lg-8 mx-auto">Articles on fintech, software development, and consumer tech for Kenya and beyond.</p>
    </div>

    <div class="row g-4">
        <?php if (!empty($posts)): ?>
            <?php foreach ($posts as $post): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm blueprint-card overflow-hidden">
                        <div class="position-relative">
                            <img src="<?= esc($post->featured_image_url, 'attr') ?>" class="card-img-top" alt="<?= esc($post->title, 'attr') ?>" style="height: 220px; object-fit: cover;" loading="lazy">
                            <?php if (!empty($post->category_name)): ?>
                                <span class="position-absolute top-0 end-0 m-3 badge bg-primary"><?= esc($post->category_name) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            <h4 class="card-title fw-bold mb-3"><?= esc($post->title) ?></h4>
                            <p class="card-text text-body-secondary small mb-3">
                                <i class="bi bi-calendar3 me-1"></i> <?= esc($post->published_at ? $post->published_at->toFormattedDateString() : 'Not Set') ?>
                                <span class="mx-2 text-primary-emphasis opacity-25">|</span>
                                <i class="bi bi-person me-1"></i> <?= esc($post->author_name) ?>
                            </p>
                            <p class="card-text text-body-secondary flex-grow-1"><?= esc($post->excerpt) ?></p>
                            <a href="<?= url_to('blog.show', $post->slug) ?>" class="btn btn-outline-primary mt-3 stretched-link rounded-pill">Read Article</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-journal-x display-1 text-body-tertiary mb-3"></i>
                <p class="lead text-muted">No blog posts have been published yet. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($pager): ?>
        <div class="d-flex justify-content-center mt-5">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>