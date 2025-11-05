<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<style>
    /* MODIFICATION: Retained original SVG for light mode */
    .auth-illustration {
        background: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%230d6efd" fill-opacity="1" d="M0,192L48,176C96,160,192,128,288,133.3C384,139,480,181,576,208C672,235,768,245,864,224C960,203,1056,149,1152,122.7C1248,96,1344,96,1392,96L1440,96L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path></svg>') no-repeat center center;
        background-size: cover;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        padding: 3rem;
        border-top-right-radius: 0.75rem;
        border-bottom-right-radius: 0.75rem;
    }

    /* MODIFICATION: Added a dark-mode specific SVG background */
    html[data-bs-theme="dark"] .auth-illustration {
        background: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%231a1a2e" fill-opacity="1" d="M0,192L48,176C96,160,192,128,288,133.3C384,139,480,181,576,208C672,235,768,245,864,224C960,203,1056,149,1152,122.7C1248,96,1344,96,1392,96L1440,96L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path></svg>') no-repeat center center;
        background-size: cover;
        color: var(--text-heading);
    }
    
    .illustration-content h4 {
        font-weight: 700;
        font-size: 1.75rem;
    }
    .illustration-content p {
        font-size: 1.1rem;
        opacity: 0.9;
    }
    html[data-bs-theme="light"] .illustration-content p {
        color: white;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity()) {
                   // fbq('track', 'CompleteRegistration');
                }
            });
        }
    });
</script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card blueprint-card overflow-hidden">
                <div class="row g-0">
                    <div class="col-lg-6">
                        <div class="card-body p-4 p-md-5">
                            <h3 class="text-center mb-4 fw-bold">Unlock Your Digital Toolkit</h3>
                            <?php if (isset($validation)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?= $validation->listErrors() ?>
                                </div>
                            <?php endif; ?>
                            <form action="<?= url_to('register.store') ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="username" name="username" placeholder="johndoe" value="<?= esc(old('username')) ?>" required>
                                    <label for="username">Username</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?= esc(old('email')) ?>" required>
                                    <label for="email">Email address</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                    <label for="password">Password</label>
                                </div>
                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="confirmpassword" name="confirmpassword" placeholder="Confirm Password" required>
                                    <label for="confirmpassword">Confirm Password</label>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">I agree to the <a href="<?= url_to('terms') ?>" target="_blank">Terms and Conditions</a></label>
                                </div>
                                <div class="mb-3">
                                    <div class="g-recaptcha" data-sitekey="<?= config('Config\Custom\Recaptcha')->siteKey ?>"></div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" id="submitButton" class="btn btn-primary btn-lg">Register</button>
                                </div>
                                <p class="mt-4 text-center text-muted">Already have an account? <a href="<?= url_to('login') ?>">Login here</a></p>
                            </form>
                        </div>
                    </div>
                     <div class="col-lg-6 d-none d-lg-block auth-illustration">
                        <div class="illustration-content text-center">
                             <i class="bi bi-gift-fill text-primary" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                            <h4>Join Our Platform</h4>
                            <p>Your free account comes with <strong>Ksh. 30</strong> in starter credits to try our AI and Crypto tools right away.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
