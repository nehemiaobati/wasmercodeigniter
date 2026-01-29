<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>

<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container">


    <a href="<?= base_url("assets/Documentation AGI V5.2.pdf") ?>" class="btn btn-primary doc-link mt-4" >
        AGI Documentation
    </a>

</div>

<?= $this->endSection() ?>