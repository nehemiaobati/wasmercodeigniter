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

                    <h2>1. Information We Collect</h2>
                    <p>We may collect personal identification information from Users in a variety of ways, including, but not limited to, when Users visit our site, register on the site, and in connection with other activities, services, features or resources we make available on our Service. Users may be asked for, as appropriate, name, email address, and other details.</p>

                    <h2>2. How We Use Collected Information</h2>
                    <p>AFRIKENKID may collect and use Users personal information for the following purposes:</p>
                    <ul>
                        <li>To improve customer service</li>
                        <li>To personalize user experience</li>
                        <li>To process payments</li>
                        <li>To send periodic emails</li>
                    </ul>

                    <h2>3. How We Protect Your Information</h2>
                    <p>We adopt appropriate data collection, storage and processing practices and security measures to protect against unauthorized access, alteration, disclosure or destruction of your personal information, username, password, transaction information and data stored on our Service.</p>

                    <h2>4. Sharing Your Personal Information</h2>
                    <p>We do not sell, trade, or rent Users personal identification information to others. We may share generic aggregated demographic information not linked to any personal identification information regarding visitors and users with our business partners, trusted affiliates and advertisers.</p>

                    <h2>5. Your Rights</h2>
                    <p>You have the right to access, update, or delete your personal information. If you wish to exercise any of these rights, please contact us.</p>

                    <h2>6. Changes to This Privacy Policy</h2>
                    <p>AFRIKENKID has the discretion to update this privacy policy at any time. When we do, we will revise the updated date at the bottom of this page. We encourage Users to frequently check this page for any changes to stay informed about how we are helping to protect the personal information we collect.</p>

                    <p class="mt-5 text-muted">Last updated: <?= date('F d, Y') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>