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
            // Show the banner
            banner.style.display = 'block';

            const form = banner.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Accepting...';

                    fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // Smooth fade out
                                banner.style.transition = 'opacity 0.5s ease-out';
                                banner.style.opacity = '0';
                                setTimeout(() => {
                                    banner.style.display = 'none';
                                }, 500);
                            } else {
                                // Revert button state on failure logic (though unlikely for simple cookie set)
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }
                        })
                        .catch(error => {
                            console.error('Error accepting cookies:', error);
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        });
                });
            }
        }
    });
</script>