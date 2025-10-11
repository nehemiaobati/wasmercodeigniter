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
                <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
                <h3 class="fw-bold mt-3">Set a New Password</h3>
                <p class="text-muted">Please enter and confirm your new password below.</p>
            </div>

            <form action="<?= url_to('auth.update_password') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>">

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="New Password" required>
                    <label for="password">New Password</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="confirmpassword" name="confirmpassword" placeholder="Confirm New Password" required>
                    <label for="confirmpassword">Confirm New Password</label>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>