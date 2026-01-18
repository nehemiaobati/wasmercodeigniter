<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .legal-content-card {
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        border: none;
    }

    .legal-content-card h1 {
        font-weight: 700;
    }

    .legal-content-card h2 {
        font-weight: 600;
        margin-top: 2rem;
    }

    .legal-content-card h3 {
        font-weight: 600;
        margin-top: 1.5rem;
        font-size: 1.2rem;
    }
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
                    <p>We may collect personal identification information from Users in a variety of ways, including, but not limited to, when Users visit our site, register on the site, and in connection with other activities, services, features or resources we make available on our Service. Users may be asked for, as appropriate, name, email address, and other details. We collect this data in compliance with the principles of the <strong>Data Protection Act, 2019</strong> of Kenya.</p>

                    <h2>2. How We Use Collected Information</h2>
                    <p>AFRIKENKID may collect and use Users personal information for the following purposes:</p>
                    <ul>
                        <li>To improve customer service</li>
                        <li>To personalize user experience</li>
                        <li>To process payments</li>
                        <li>To send periodic emails</li>
                    </ul>

                    <h3>Legal Basis for Processing</h3>
                    <p>Under the Kenya Data Protection Act, 2019, we process your personal data under the following lawful bases:</p>
                    <ul>
                        <li><strong>Consent:</strong> You have given clear consent for us to process your personal data for a specific purpose (e.g., subscribing to newsletters).</li>
                        <li><strong>Contract:</strong> Processing is necessary for a contract we have with you, or because you have asked us to take specific steps before entering into a contract (e.g., processing transactions).</li>
                        <li><strong>Legal Obligation:</strong> Processing is necessary for us to comply with the law (e.g., tax reporting).</li>
                        <li><strong>Legitimate Interests:</strong> Processing is necessary for our legitimate interests or the legitimate interests of a third party, unless there is a good reason to protect your personal data which overrides those legitimate interests.</li>
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

                    <h2>6. Your Rights Under the Data Protection Act (Kenya)</h2>
                    <p>As a data subject in Kenya, you have the following rights regarding your personal data:</p>
                    <ul>
                        <li><strong>Right to be Informed:</strong> You have the right to be informed of the use to which your personal data is to be put.</li>
                        <li><strong>Right of Access:</strong> You have the right to access your personal data in our custody.</li>
                        <li><strong>Right to Rectification:</strong> You have the right to object to the processing of all or part of your personal data and to correction of false or misleading data.</li>
                        <li><strong>Right to Erasure ("Right to be Forgotten"):</strong> You have the right to ask us to delete false or misleading data about you.</li>
                        <li><strong>Right to Object:</strong> You have the right to object to the processing of all or part of your personal data.</li>
                        <li><strong>Right to Data Portability:</strong> You have the right to receive your personal data in a structured, commonly used, and machine-readable format.</li>
                        <li><strong>Automated Decision Making:</strong> You have the right not to be subjected to a decision based solely on automated processing, including profiling, which produces legal effects concerning you.</li>
                    </ul>
                    <p>If you wish to exercise any of these rights, please contact us. We will respond to your request within the statutory timelines.</p>

                    <h2>7. Data Retention</h2>
                    <p>We will only retain your personal data for as long as necessary to fulfil the purposes we collected it for, including for the purposes of satisfying any legal, accounting, or reporting requirements. When we no longer need your personal data, we will securely delete or anonymize it.</p>

                    <h2>8. International Data Transfers</h2>
                    <p>Your information may be transferred to — and maintained on — computers located outside of your state, province, country, or other governmental jurisdiction where the data protection laws may differ than those from your jurisdiction. If we transfer your data outside of Kenya, we will ensure that appropriate safeguards are in place in accordance with the Data Protection Act, 2019.</p>

                    <h2>9. Data Breach Procedures</h2>
                    <p>In the event of a data breach that is likely to result in a risk to your rights and freedoms, we will notify the Office of the Data Protection Commissioner (ODPC) within 72 hours of becoming aware of the breach. If the breach is likely to result in a high risk to your rights and freedoms, we will also notify you without undue delay.</p>

                    <h2>10. Changes to This Privacy Policy</h2>
                    <p>AFRIKENKID has the discretion to update this privacy policy at any time. When we do, we will revise the updated date at the bottom of this page. We encourage Users to frequently check this page for any changes to stay informed about how we are helping to protect the personal information we collect.</p>

                    <h2>11. Contact Us & Data Controller</h2>
                    <p>If you have any questions about this Privacy Policy, the practices of this site, or your dealings with this site, please contact us. For the purposes of the Data Protection Act, <strong>AFRIKENKID</strong> is the Data Controller.</p>
                    <p>
                        <strong>Email:</strong> <a href="mailto:afrikenkid@gmail.com">afrikenkid@gmail.com</a><br>
                        <strong>Phone:</strong> <a href="tel:254794587533">+254 794 587 533</a>
                    </p>

                    <p class="mt-5 text-muted">Last updated: January 18, 2026</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>