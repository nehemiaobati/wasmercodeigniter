<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card blueprint-card">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-key-fill text-primary" style="font-size: 3rem;"></i>
                        <h3 class="fw-bold mt-3">Forgot Your Password?</h3>
                        <p class="text-muted">No problem. Enter your email, and we'll send a secure link to reset it.</p>
                    </div>

                    <form action="<?= url_to('auth.send_reset_link') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-floating mb-4">
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?= old('email') ?>" required>
                            <label for="email">Email Address</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">Send Reset Link</button>
                        </div>
                    </form>
                    <p class="mt-4 text-center text-muted">Remember your password? <a href="<?= url_to('login') ?>">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
