<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container documentation-container" style="min-height: 700px;">
    <h1>Application Documentation</h1>

    <p>Below are links to the official documentation:</p>

    <a href="<?= url_to('web') ?>"  class="btn btn-primary doc-link mt-4" >
        Web Documentation
    </a>

    <a href="<?= url_to('agi') ?>" class="btn btn-primary doc-link mt-4" >
        AGI Documentation
    </a>
</div>

<?= $this->endSection() ?>
