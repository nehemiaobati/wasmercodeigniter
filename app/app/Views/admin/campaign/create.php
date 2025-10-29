<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container my-5">
<div class="d-flex align-items-center mb-4">
<a href="<?= url_to('admin.index') ?>" class="btn btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i> Back</a>
<h1 class="fw-bold mb-0">Create Email Campaign</h1>
</div>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card blueprint-card">
            <div class="card-body p-4 p-md-5">
                <p class="text-muted">Compose your message below or select a saved template to get started. The email will be sent to all registered users.</p>
                
                <form action="<?= url_to('admin.campaign.send') ?>" method="post" id="campaignForm">
                    <?= csrf_field() ?>

                    <?php if (!empty($campaigns)): ?>
                    <div class="mb-3">
                        <label for="campaignTemplate" class="form-label fw-bold">Load a Template</label>
                        <div class="input-group">
                            <select class="form-select" id="campaignTemplate">
                                <option selected disabled>Select a saved campaign...</option>
                                <?php foreach ($campaigns as $campaign): ?>
                                    <option value="<?= esc($campaign->id) ?>" data-subject="<?= esc($campaign->subject) ?>" data-body="<?= esc($campaign->body) ?>">
                                        <?= esc($campaign->subject) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-danger" type="button" id="deleteTemplateBtn" disabled>
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="subject" name="subject" placeholder="Email Subject" value="<?= old('subject') ?>" required>
                        <label for="subject">Email Subject</label>
                    </div>

                    <div class="form-floating mb-4">
                        <textarea class="form-control" id="message" name="message" placeholder="Your message here... You can use HTML tags like <strong>, <a>, etc." style="height: 300px" required><?= old('message') ?></textarea>
                        <label for="message">Message Body (HTML supported)</label>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-outline-secondary" formaction="<?= url_to('admin.campaign.save') ?>">
                            <i class="bi bi-save"></i> Save as Template
                        </button>
                        <button type="submit" class="btn btn-primary fw-bold" onclick="return confirm('Are you sure you want to send this campaign to all users?');">
                            <i class="bi bi-send-fill"></i> Send Campaign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
const templateSelect = document.getElementById('campaignTemplate');
const subjectInput = document.getElementById('subject');
const messageTextarea = document.getElementById('message');
const deleteButton = document.getElementById('deleteTemplateBtn');
const csrfToken = document.querySelector('input[name="<?= csrf_token() ?>"]').value;

if (templateSelect) {
templateSelect.addEventListener('change', function() {
const selectedOption = this.options[this.selectedIndex];

deleteButton.disabled = (this.value === "" || selectedOption.disabled);

if (selectedOption && !selectedOption.disabled) {
const subject = selectedOption.getAttribute('data-subject');
const body = selectedOption.getAttribute('data-body');

if (subject) subjectInput.value = subject;
if (body) messageTextarea.value = body;
}
});
}

if (deleteButton) {
deleteButton.addEventListener('click', function() {
const selectedOption = templateSelect.options[templateSelect.selectedIndex];
if (!selectedOption || selectedOption.disabled) return;

const campaignId = selectedOption.value;
const campaignSubject = selectedOption.getAttribute('data-subject');

// Corrected template literal for confirm message
if (confirm(`Are you sure you want to delete the template: "${campaignSubject}"? This action cannot be undone.`)) {
const tempForm = document.createElement('form');
tempForm.method = 'POST';
tempForm.action = `<?= rtrim(route_to('admin.campaign.delete', 0), '0') ?>${campaignId}`;

const csrfInput = document.createElement('input');
csrfInput.type = 'hidden';
csrfInput.name = '<?= csrf_token() ?>';
csrfInput.value = csrfToken;
tempForm.appendChild(csrfInput);

document.body.appendChild(tempForm);
tempForm.submit();
}
});
}
});
</script>
<?= $this->endSection() ?>
