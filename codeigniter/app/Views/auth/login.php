<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .auth-section {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .auth-card {
        border-radius: 1rem;
        box-shadow: 0 1rem 3rem rgba(0,0,0,.1);
        border: none;
        overflow: hidden;
    }
    .auth-card-header {
        background-color: var(--primary-color);
        color: white;
        padding: 2rem;
        text-align: center;
    }
    .auth-card-header h3 {
        margin: 0;
        font-weight: 700;
    }
    .auth-card-body {
        padding: 2.5rem;
    }
    .auth-card .form-floating label {
        color: #6c757d;
    }
    .auth-card .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .auth-card .btn-primary {
        font-weight: 600;
        padding: 0.75rem 1rem;
    }
    .auth-illustration {
        background: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%230d6efd" fill-opacity="1" d="M0,256L48,240C96,224,192,192,288,186.7C384,181,480,203,576,229.3C672,256,768,288,864,288C960,288,1056,256,1152,234.7C1248,213,1344,203,1392,197.3L1440,192L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path></svg>') no-repeat center center;
        background-size: cover;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        padding: 3rem;
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
<div class="container auth-section">
    <div class="row w-100 justify-content-center">
        <div class="col-lg-10">
            <div class="card auth-card">
                <div class="row g-0">
                    <div class="col-lg-6 d-none d-lg-block auth-illustration">
                        <div class="illustration-content text-center">
                             <i class="bi bi-box-arrow-in-right" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                            <h4>Welcome Back!</h4>
                            <p>Enter your credentials to access your dashboard and our powerful suite of tools.</p>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="auth-card-body">
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
