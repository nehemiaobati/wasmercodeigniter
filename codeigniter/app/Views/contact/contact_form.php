<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .contact-card {
        border-radius: 1rem;
        box-shadow: 0 1rem 3rem rgba(0,0,0,.075);
        border: none;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card contact-card">
                <div class="card-body p-5">
                    <div class="text-center mb-5">
                        <i class="bi bi-envelope-heart-fill text-primary" style="font-size: 3rem;"></i>
                        <h2 class="fw-bold mt-3">Get in Touch</h2>
                        <p class="text-muted">Have a question or need support? Fill out the form below.</p>
                    </div>

                    <?= form_open(url_to('contact.send')) ?>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" value="<?= old('name') ?>" required>
                            <label for="name">Name</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" value="<?= old('email') ?>" required>
                            <label for="email">Email</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" value="<?= old('subject') ?>" required>
                            <label for="subject">Subject</label>
                        </div>
                        <div class="form-floating mb-4">
                            <textarea class="form-control" id="message" name="message" placeholder="Your Message" style="height: 150px" required><?= old('message') ?></textarea>
                            <label for="message">Message</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">Send Message</button>
                        </div>
                    <?= form_close() ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>