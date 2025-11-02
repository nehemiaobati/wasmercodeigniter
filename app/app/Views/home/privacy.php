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
    .legal-content-card h3 { font-weight: 600; margin-top: 1.5rem; font-size: 1.2rem; }
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

                    <!-- NEW SECTION START -->
                    <h2>4. Cookie Policy</h2>
                    <p>Our website uses cookies to enhance user experience, ensure security, and provide essential functionality. A cookie is a small text file stored on your device. By using our Service, you agree to the use of these cookies as described below.</p>
                    
                    <h3>Types of Cookies We Use</h3>
                    <ul>
                        <li><strong>Strictly Necessary Cookies:</strong> These cookies are essential for you to browse the website and use its features, such as accessing secure areas of the site. Without these cookies, services like user login and form submissions cannot be provided.</li>
                        <li><strong>Third-Party Cookies:</strong> These cookies are set by a domain other than the one you are visiting. We use them for specific functionalities.</li>
                    </ul>

                    <h3>Specific Cookies in Use</h3>
                    <ul>
                        <li><strong>Session Cookie (ci_session):</strong> This is a strictly necessary cookie used by our framework to maintain your login state as you navigate through the website. It is deleted when you close your browser.</li>
                        <li><strong>CSRF Cookie (csrf_cookie_name):</strong> This is a strictly necessary security cookie that protects our site and users from Cross-Site Request Forgery attacks on our forms.</li>
                        <li><strong>Google reCAPTCHA Cookie (_GRECAPTCHA):</strong> This third-party cookie is set by Google on our registration, login, and contact forms. It is used to distinguish between human users and automated bots to prevent spam and abuse. Use of Google reCAPTCHA is subject to Google's <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Privacy Policy</a> and <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer">Terms of Service</a>.</li>
                        <li><strong>Consent Cookie (user_cookie_consent):</strong> This cookie is set when you accept our cookie policy. It stores your consent preference and prevents the cookie banner from reappearing on subsequent visits for one year.</li>
                    </ul>

                    <h3>Managing Cookies</h3>
                    <p>You can control and/or delete cookies as you wish. Most web browsers allow some control of most cookies through the browser settings. To find out more about cookies, including how to see what cookies have been set, visit <a href="https://www.aboutcookies.org" target="_blank" rel="noopener noreferrer">www.aboutcookies.org</a>.</p>
                    <!-- NEW SECTION END -->

                    <h2>5. Sharing Your Personal Information</h2>
                    <p>We do not sell, trade, or rent Users personal identification information to others. We may share generic aggregated demographic information not linked to any personal identification information regarding visitors and users with our business partners, trusted affiliates and advertisers.</p>

                    <h2>6. Your Rights</h2>
                    <p>You have the right to access, update, or delete your personal information. If you wish to exercise any of these rights, please contact us.</p>

                    <h2>7. Changes to This Privacy Policy</h2>
                    <p>AFRIKENKID has the discretion to update this privacy policy at any time. When we do, we will revise the updated date at the bottom of this page. We encourage Users to frequently check this page for any changes to stay informed about how we are helping to protect the personal information we collect.</p>

                    <p class="mt-5 text-muted">Last updated: <?= date('F d, Y') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
