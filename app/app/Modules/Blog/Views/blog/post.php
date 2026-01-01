<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('public/assets/highlight/styles/atom-one-dark.min.css') ?>">
<style>
  .article-body {
    font-size: 1.125rem;
    line-height: 1.8;
  }

  .article-body h2,
  .article-body h3 {
    margin-top: 3rem;
    margin-bottom: 1.5rem;
    font-weight: 700;
    color: var(--bs-heading-color);
  }

  .article-body p {
    margin-bottom: 1.5rem;
  }

  .article-body img {
    max-width: 100%;
    height: auto;
    border-radius: 1rem;
    margin: 2rem 0;
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.05);
  }

  .article-body pre {
    border-radius: 0.75rem;
    padding: 1rem;
    margin: 2rem 0;
    background-color: #282c34;
    /* Atom One Dark background */
  }

  .article-body pre code {
    font-family: 'Fira Code', 'Courier New', Courier, monospace;
    font-size: 0.95rem;
  }

  .post-header-gradient {
    background: var(--hero-gradient);
    padding: 6rem 0;
    margin-bottom: -4rem;
    color: white;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Post Header -->
<header class="post-header-gradient text-center">
  <div class="container">
    <div class="row">
      <div class="col-lg-8 mx-auto">
        <div class="mb-3">
          <?php if (!empty($post->category_name)): ?>
            <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold shadow-sm"><?= esc($post->category_name) ?></span>
          <?php endif; ?>
        </div>
        <h1 class="display-4 fw-bold mb-3"><?= esc($post->title) ?></h1>
        <div class="lead opacity-75">
          <i class="bi bi-calendar3 me-1"></i> <?= esc($post->published_at ? \CodeIgniter\I18n\Time::parse($post->published_at)->toLocalizedString('MMMM d, yyyy') : 'Not Set') ?>
          <span class="mx-2">|</span>
          <i class="bi bi-person me-1"></i> By <?= esc($post->author_name) ?>
        </div>
      </div>
    </div>
  </div>
</header>

<div class="container mb-5">
  <div class="row">
    <div class="col-lg-8 mx-auto">
      <div class="blueprint-card p-4 p-md-5 mt-n5 position-relative z-1 shadow-lg border-0">
        <?php if (!empty($post->featured_image_url)): ?>
          <figure class="mb-5">
            <img class="img-fluid rounded-3 shadow" src="<?= esc($post->featured_image_url, 'attr') ?>" alt="<?= esc($post->title, 'attr') ?>" loading="lazy">
          </figure>
        <?php endif; ?>

        <section class="article-body">
          <?php if (!empty($post->excerpt)): ?>
            <div class="lead text-body-secondary mb-5 border-start border-4 border-primary ps-4 fst-italic">
              <?= esc($post->excerpt) ?>
            </div>
          <?php endif; ?>

          <?php
          $parsedown = new \Parsedown();
          $parsedown->setSafeMode(true);
          if (!empty($post->body_content)) {
            foreach ($post->body_content as $block) {
              switch ($block->type) {
                case 'text':
                  echo $parsedown->text($block->content);
                  break;
                case 'image':
                  echo '<img src="' . esc($block->url, 'attr') . '" alt="Blog image content" loading="lazy">';
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

        <hr class="my-5 opacity-10">

        <div class="d-flex justify-content-between align-items-center">
          <a href="<?= url_to('blog.index') ?>" class="btn btn-outline-primary rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i> Back to Blog
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('public/assets/highlight/highlight.js') ?>"></script>
<script>
  document.addEventListener('DOMContentLoaded', (event) => {
    hljs.highlightAll();
  });
</script>
<?= $this->endSection() ?>