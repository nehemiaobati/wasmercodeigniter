<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container documentation-container">
    <h1>Application Documentation</h1>

    <p>Below are links to the official documentation:</p>

    <a href="<?= base_url('assets/Afrikenkid Web Platform.pdf') ?>" class="btn btn-primary doc-link" target="_blank">
        Afrikenkid Web Platform Documentation
    </a>

    <a href="<?= base_url('assets/Documentation AGI V5.1.pdf') ?>" class="btn btn-primary doc-link" target="_blank">
        AGI V5.1 Documentation
    </a>
</div>

<?= $this->endSection() ?>
