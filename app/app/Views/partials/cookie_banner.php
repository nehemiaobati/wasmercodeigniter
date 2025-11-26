<!-- Cookie Consent Banner -->
<style>
    .cookie-consent-banner {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: rgba(33, 37, 41, 0.95);
        color: #f8f9fa;
        padding: 1.5rem 1rem;
        z-index: 1055;
        /* Higher than most elements */
        backdrop-filter: blur(5px);
        display: none;
        /* Hidden by default, shown by JS */
        box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.1);
    }
</style>

<div id="cookieConsentBanner" class="cookie-consent-banner">
    <div class="container">
        <div class="d-md-flex align-items-center justify-content-between">
            <p class="mb-3 mb-md-0 me-md-4">
                This website uses cookies to ensure you get the best experience on our website. This includes cookies necessary for the website's operation, security, and to comply with our legal obligations.
                <a href="<?= url_to('privacy') ?>" class="text-white fw-bold">Learn more</a>.
            </p>
            <div class="d-flex flex-shrink-0">
                <form action="<?= url_to('cookie.accept') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary fw-bold w-100">Accept</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('cookieConsentBanner');
        if (banner) {
            banner.style.display = 'block';
        }
    });
</script>