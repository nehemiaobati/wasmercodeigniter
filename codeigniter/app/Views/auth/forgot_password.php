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
        max-width: 500px;
        width: 100%;
    }
    .auth-card-body {
        padding: 2.5rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container auth-section">
    <div class="card auth-card">
        <div class="auth-card-body">
            <div class="text-center mb-4">
                <i class="bi bi-key-fill text-primary" style="font-size: 3rem;"></i>
                <h3 class="fw-bold mt-3">Forgot Your Password?</h3>
                <p class="text-muted">No problem. Enter your email address below, and we'll send you a link to reset it.</p>
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
<?= $this->endSection() ?>