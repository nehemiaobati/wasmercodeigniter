<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<style>
    .auth-illustration {
        background: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%230d6efd" fill-opacity="1" d="M0,256L48,240C96,224,192,192,288,186.7C384,181,480,203,576,229.3C672,256,768,288,864,288C960,288,1056,256,1152,234.7C1248,213,1344,203,1392,197.3L1440,192L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path></svg>') no-repeat center center;
        background-size: cover;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        padding: 3rem;
        border-top-left-radius: 0.75rem;
        border-bottom-left-radius: 0.75rem;
    }
    .illustration-content h4 {
        font-weight: 700;
        font-size: 1.75rem;
    }
    .illustration-content p {
        font-size: 1.1rem;
        opacity: 0.9;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card blueprint-card overflow-hidden">
                <div class="row g-0">
                    <div class="col-lg-6 d-none d-lg-block auth-illustration">
                        <div class="illustration-content text-center">
                             <i class="bi bi-box-arrow-in-right" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                            <h4>Let's Get Back to It!</h4>
                            <p>Your AI assistant and crypto data dashboard are waiting. Log in to continue your work.</p>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card-body p-4 p-md-5">
                            <h3 class="text-center mb-4 fw-bold">Sign In</h3>
                            <?php if (isset($validation)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?= $validation->listErrors() ?>
                                </div>
                            <?php endif; ?>
                            <form action="<?= url_to('login.authenticate') ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?= esc(old('email')) ?>" required>
                                    <label for="email">Email address</label>
                                </div>
                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                    <label for="password">Password</label>
                                </div>
                                <div class="mb-3">
                                    <div class="g-recaptcha" data-sitekey="<?= config('Recaptcha')->siteKey ?>"></div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                                </div>
                                <p class="mt-4 text-center text-muted">Don't have an account? <a href="<?= url_to('register') ?>">Register here</a></p>
                                <p class="mt-4 text-center text-muted"><a href="<?= url_to('auth.forgot_password') ?>">Forgot Password?</a></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
