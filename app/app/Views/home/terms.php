<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .legal-content-card {
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
        border: none;
    }
    .legal-content-card h1 { font-weight: 700; }
    .legal-content-card h2 { font-weight: 600; margin-top: 2rem; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card legal-content-card">
                 <div class="card-body p-5">
                    <h1 class="card-title text-center mb-5"><?= esc($pageTitle) ?></h1>
                    
                    <h2>1. Acceptance of Terms</h2>
                    <p>By accessing and using AFRIKENKID ("the Service"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by these terms, please do not use the Service.</p>

                    <h2>2. User Conduct</h2>
                    <p>You agree to use the Service only for lawful purposes. You are prohibited from posting on or transmitting through the Service any material that is harmful, threatening, abusive, harassing, defamatory, vulgar, obscene, sexually explicit, profane, hateful, or otherwise objectionable.</p>

                    <h2>3. Intellectual Property</h2>
                    <p>The Service and its original content, features, and functionality are owned by AFRIKENKID and are protected by international copyright, trademark, patent, trade secret, and other intellectual property or proprietary rights laws.</p>

                    <h2>4. Limitation of Liability</h2>
                    <p>In no event shall AFRIKENKID, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your access to or use of or inability to access or use the Service.</p>

                    <h2>5. Governing Law</h2>
                    <p>These Terms shall be governed and construed in accordance with the laws of the jurisdiction in which the company is established, without regard to its conflict of law provisions.</p>

                    <h2>6. Changes to Terms</h2>
                    <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. We will provide notice of any changes by posting the new Terms of Service on this page.</p>

                    <p class="mt-5 text-muted">Last updated: <?= date('F d, Y') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>