<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/highlight/styles/atom-one-dark.min.css') ?>">
<style>
    .article-body h2, .article-body h3 {
        margin-top: 2.5rem;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }
    .article-body p { line-height: 1.8; }
    .article-body img { max-width: 100%; height: auto; border-radius: 0.5rem; margin: 1.5rem 0; }
    .article-body pre { border-radius: 0.5rem; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
  <div class="row">
    <div class="col-lg-8 mx-auto">
      <main>
        <article>
          <header class="mb-4">
            <h1 class="fw-bolder mb-1"><?= esc($post->title) ?></h1>
            <div class="text-muted fst-italic mb-2">
              Posted on <?= esc($post->published_at ? \CodeIgniter\I18n\Time::parse($post->published_at)->toLocalizedString('MMMM d, yyyy') : 'Not Set') ?> by <?= esc($post->author_name) ?>
            </div>
            <?php if (!empty($post->category_name)): ?>
            <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill"><?= esc($post->category_name) ?></span>
            <?php endif; ?>
          </header>

          <?php if (!empty($post->featured_image_url)): ?>
          <figure class="mb-4">
            <img class="img-fluid rounded" src="<?= esc($post->featured_image_url, 'attr') ?>" alt="<?= esc($post->title, 'attr') ?>">
          </figure>
          <?php endif; ?>

          <section class="mb-5 article-body">
            <?php if (!empty($post->excerpt)): ?>
            <p class="fs-5 mb-4 lead"><?= esc($post->excerpt) ?></p>
            <?php endif; ?>
            
            <?php
            $parsedown = new \Parsedown();
            if (!empty($post->body_content)) {
                foreach ($post->body_content as $block) {
                    switch ($block->type) {
                        case 'text':
                            echo $parsedown->text($block->content);
                            break;
                        case 'image':
                            echo '<img src="' . esc($block->url, 'attr') . '" alt="Blog image content">';
                            break;
                        case 'code':
                            $lang = !empty($block->language) ? esc($block->language, 'attr') : 'plaintext';
                            echo '<pre><code class="language-' . $lang . '">' . esc($block->code) . '</code></pre>';
                            break;
                    }
                }
            }
            ?>
          </section>
        </article>
      </main>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/highlight/highlight.js') ?>"></script>
<script>hljs.highlightAll();</script>
<?= $this->endSection() ?>
