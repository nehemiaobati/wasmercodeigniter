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
                <p>These Terms of Service ("Terms") constitute a legally binding agreement between you ("User," "you," or "your") and AFRIKENKID ("Company," "we," "us," or "our"). By accessing or using our Service, you confirm that you are of legal age to form a binding contract and agree to comply with all provisions herein. Your continued use of the Service following any modification to these Terms constitutes your acceptance of the revised terms. This agreement is recognized as a valid electronic contract under the Kenya Information and Communications Act.</p>

                <h2>2. Privacy and Data Protection</h2>
                <p>Your privacy is important to us. Our Privacy Policy, which is incorporated into these Terms by reference, explains how we collect, use, and protect your personal data. By using our Service, you consent to the data practices described in our Privacy Policy. We are committed to processing your personal data in compliance with Kenya's Data Protection Act, 2019. This includes adhering to principles of lawfulness, fairness, and transparency in all data handling activities.</p>

                <h2>3. User Conduct and Responsibilities</h2>
                <p>You agree not to use the Service for any unlawful purpose or in any manner inconsistent with these Terms. You are solely responsible for all content you post or transmit through the Service. Prohibited conduct includes, but is not limited to:</p>
                <p>(a) Posting or transmitting any unlawful, harmful, threatening, abusive, harassing, defamatory, vulgar, obscene, or hateful content; (b) Engaging in prohibited activities such as spamming, phishing, or fraud; (c) Attempting to gain unauthorized access to any portion of the Service or its related systems; (d) Interfering with or disrupting the Service or its servers; (e) Circumventing any security measures intended to prevent or restrict access to the Service; (f) Infringing upon the intellectual property rights of others.</p>

                <h2>4. Intellectual Property</h2>
                <p>All content, trademarks, logos, and other intellectual property on the Service (including but not limited to text, graphics, logos, images, and software) is the exclusive property of AFRIKENKID and is protected by international copyright, trademark, and other applicable laws. You are granted a limited, non-transferable, non-exclusive license to access and use the Service for personal, non-commercial purposes only. You may not reproduce, distribute, modify, or create derivative works of our intellectual property without our express written consent.</p>

                <h2>5. Disclaimer of Warranties and Limitation of Liability</h2>
                <p>The Service is provided on an "as is" and "as available" basis without any warranties of any kind, either express or implied. To the fullest extent permitted by law, AFRIKENKID disclaims all warranties, including but not limited to the warranty of merchantability, fitness for a particular purpose, and non-infringement.</p>
                <p>In no event shall AFRIKENKID or its officers, directors, employees, or agents be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of profits, data, use, goodwill, or other intangible losses resulting from: (a) your access to or use of, or inability to access or use, the Service; (b) any conduct or content of any third party on the Service; (c) any unauthorized access, use, or alteration of your transmissions or content; (d) any errors or omissions in the Service; or (e) any other matter relating to the Service. Our total liability to you for any and all claims arising out of or relating to these Terms or your use of the Service shall not exceed the amount you have paid to us, if any, for use of the Service.</p>

                <h2>6. Indemnification</h2>
                <p>You agree to defend, indemnify, and hold harmless AFRIKENKID, its officers, directors, employees, and agents from and against any and all claims, damages, obligations, losses, liabilities, costs, or debt, and expenses (including but not limited to attorney's fees) arising from: (a) your use of and access to the Service; (b) your violation of any term of these Terms; or (c) your violation of any third-party right, including without limitation any copyright, property, or privacy right.</p>

                <h2>7. Governing Law and Dispute Resolution</h2>
                <p>These Terms shall be governed by and construed in accordance with the laws of the Republic of Kenya, without regard to its conflict of law principles. Any dispute, controversy, or claim arising out of or relating to these Terms shall first be attempted to be resolved through amicable negotiation. If the dispute is not resolved through negotiation within thirty (30) days, the parties agree to submit the dispute to mediation or arbitration in accordance with the laws of Kenya before resorting to litigation. The venue for any such legal proceeding shall be the courts of competent jurisdiction in Kenya.</p>

                <h2>8. Changes to Terms</h2>
                <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. We will provide notice of material changes by posting the revised Terms on this page and updating the "Last updated" date. Your continued use of the Service after any such changes constitutes your acceptance of the new Terms.</p>

                <h2>9. Termination</h2>
                <p>We may terminate or suspend your access to the Service at any time, without prior notice or liability, for any reason whatsoever, including without limitation if you breach these Terms. Upon termination, your right to use the Service will immediately cease. All provisions of the Terms which by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity, and limitations of liability.</p>

                <h2>10. Entire Agreement</h2>
                <p>These Terms, together with our Privacy Policy and any other legal notices published by us on the Service, shall constitute the entire agreement between you and AFRIKENKID concerning the Service and supersede all prior or contemporaneous communications and proposals, whether electronic, oral, or written, between you and us.</p>

                <h2>11. Contact Information</h2>
                <p>If you have any questions about these Terms, please contact us at <a href="mailto:afrikenkid@gmail.com">Email</a> or <a href="tel:254794587533">Phone</a>.</p>

                <p class="mt-5 text-muted">Last updated: <?= date('F d, Y') ?></p>
            </div>
        </div>
    </div>
</div>
</div>
<?= $this->endSection() ?>

