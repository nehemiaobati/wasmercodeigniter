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
        z-index: 1055; /* Higher than most elements */
        backdrop-filter: blur(5px);
        display: none; /* Hidden by default, shown by JS */
        box-shadow: 0 -5px 15px rgba(0,0,0,0.1);
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
                <button id="acceptCookiesBtn" class="btn btn-primary fw-bold w-100">Accept</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('cookieConsentBanner');
        const acceptBtn = document.getElementById('acceptCookiesBtn');

        // Function to check if consent has been given
        function hasConsent() {
            return document.cookie.split(';').some((item) => item.trim().startsWith('user_cookie_consent=accepted'));
        }

        // Show banner if no consent is found
        if (!hasConsent()) {
            banner.style.display = 'block';
        }

        // Set cookie on accept
        acceptBtn.addEventListener('click', function() {
            const expiryDate = new Date();
            expiryDate.setFullYear(expiryDate.getFullYear() + 1); // Set cookie for 1 year
            document.cookie = `user_cookie_consent=accepted; expires=${expiryDate.toUTCString()}; path=/; SameSite=Lax; Secure`;
            
            // Hide the banner with a fade-out effect
            banner.style.transition = 'opacity 0.5s ease';
            banner.style.opacity = '0';
            setTimeout(() => {
                banner.style.display = 'none';
            }, 500);
        });
    });
</script>
