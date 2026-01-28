<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<div class="container my-5">
    <div class="blueprint-header mb-4">
        <h1><?= esc($formTitle) ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= url_to('admin.affiliate.index') ?>">Affiliate Links</a>
                </li>
                <li class="breadcrumb-item active"><?= $link ? 'Edit' : 'New' ?></li>
            </ol>
        </nav>
    </div>

    <div class="card blueprint-card">
        <div class="card-body">
            <form action="<?= esc($formAction) ?>" method="post" id="affiliateForm">
                <?= csrf_field() ?>

                <!-- Title (Optional) -->
                <div class="form-floating mb-3">
                    <input type="text"
                        class="form-control"
                        id="title"
                        name="title"
                        placeholder="Title"
                        value="<?= old('title', $link->title ?? '') ?>">
                    <label for="title">Title (Optional)</label>
                    <div class="form-text">A descriptive title for internal reference</div>
                </div>

                <!-- Short URL -->
                <div class="form-floating mb-3">
                    <input type="url"
                        class="form-control"
                        id="short_url"
                        name="short_url"
                        placeholder="Short URL"
                        value="<?= old('short_url', $link->short_url ?? '') ?>"
                        required>
                    <label for="short_url">Amazon Short URL <span class="text-danger">*</span></label>
                    <div class="form-text">Example: https://amzn.to/3NCQfcG</div>
                </div>

                <!-- Code (Auto-extracted, Read-only) -->
                <div class="form-floating mb-3">
                    <input type="text"
                        class="form-control"
                        id="code"
                        name="code"
                        placeholder="Code"
                        value="<?= old('code', $link->code ?? '') ?>"
                        readonly>
                    <label for="code">Extracted Code</label>
                    <div class="form-text">Auto-extracted from the short URL</div>
                </div>

                <!-- Full URL -->
                <div class="form-floating mb-3">
                    <textarea class="form-control"
                        id="full_url"
                        name="full_url"
                        placeholder="Full URL"
                        style="height: 100px"
                        required><?= old('full_url', $link->full_url ?? '') ?></textarea>
                    <label for="full_url">Full Amazon Affiliate URL <span class="text-danger">*</span></label>
                    <div class="form-text">The complete Amazon affiliate link with tracking parameters</div>
                </div>

                <!-- Category -->
                <div class="mb-3">
                    <label for="category_id" class="form-label">Category</label>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">No Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat->id ?>" <?= old('category_id', $link->category_id ?? '') == $cat->id ? 'selected' : '' ?>>
                                    <?= esc($cat->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?= url_to('admin.affiliate.category.create') ?>" class="btn btn-outline-secondary" target="_blank">
                            <i class="bi bi-plus"></i>
                        </a>
                    </div>
                    <div class="form-text">Optional: Organize your link with a category</div>
                </div>

                <!-- Status -->
                <div class="mb-4">
                    <label class="form-label">Status</label>
                    <div class="form-check">
                        <input class="form-check-input"
                            type="radio"
                            name="status"
                            id="status_active"
                            value="active"
                            <?= old('status', $link->status ?? 'active') === 'active' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="status_active">
                            Active
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input"
                            type="radio"
                            name="status"
                            id="status_inactive"
                            value="inactive"
                            <?= old('status', $link->status ?? 'active') === 'inactive' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="status_inactive">
                            Inactive
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Affiliate Link
                    </button>
                    <a href="<?= url_to('admin.affiliate.index') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Auto-extract code from Amazon short URL
    document.getElementById('short_url').addEventListener('input', function(e) {
        const url = e.target.value;
        const codeInput = document.getElementById('code');

        // Match Amazon short URLs like https://amzn.to/3NCQfcG
        const match = url.match(/amzn\.to\/([a-zA-Z0-9]+)/i);

        if (match) {
            codeInput.value = match[1];
        } else {
            codeInput.value = '';
        }
    });

    // Trigger extraction on page load in case of validation errors with old input
    window.addEventListener('DOMContentLoaded', function() {
        const shortUrlInput = document.getElementById('short_url');
        if (shortUrlInput.value) {
            shortUrlInput.dispatchEvent(new Event('input'));
        }
    });
</script>

<?= $this->endSection() ?>