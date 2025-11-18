<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="blueprint-header text-center mb-5">
        <h1 class="fw-bold">Tech Insights & Tutorials</h1>
        <p class="lead text-muted">Articles on fintech, software development, and consumer tech for Kenya and beyond.</p>
    </div>

    <div class="row">
        <?php if (!empty($posts)): ?>
            <?php foreach ($posts as $post): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0 blueprint-card">
                        <img src="<?= esc($post->featured_image_url, 'attr') ?>" class="card-img-top" alt="<?= esc($post->title, 'attr') ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold"><?= esc($post->title) ?></h5>
                            <p class="card-text text-muted small">
                                Published on <?= esc($post->published_at ? $post->published_at->toFormattedDateString() : 'Not Set') ?> by <?= esc($post->author_name) ?>
                            </p>
                            <p class="card-text flex-grow-1"><?= esc($post->excerpt) ?></p>
                            <a href="<?= url_to('blog.show', $post->slug) ?>" class="btn btn-primary mt-auto">Read More</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <p class="text-center text-muted">No blog posts have been published yet. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>
    <div class="d-flex justify-content-center mt-4">
        <?= $pager->links() ?>
    </div>
</div>
<?= $this->endSection() ?>
