<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<div class="container my-5">
    <div class="blueprint-header mb-4">
        <h1><?= esc($formTitle) ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= url_to('admin.affiliate.categories') ?>">Categories</a>
                </li>
                <li class="breadcrumb-item active"><?= $category ? 'Edit' : 'New' ?></li>
            </ol>
        </nav>
    </div>

    <div class="card blueprint-card">
        <div class="card-body">
            <form action="<?= esc($formAction) ?>" method="post">
                <?= csrf_field() ?>

                <!-- Validation Errors -->
                <?php if (session('errors')): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach (session('errors') as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Name -->
                <div class="form-floating mb-3">
                    <input type="text"
                        class="form-control"
                        id="name"
                        name="name"
                        placeholder="Name"
                        value="<?= old('name', $category->name ?? '') ?>"
                        required>
                    <label for="name">Category Name <span class="text-danger">*</span></label>
                    <div class="form-text">A descriptive name for the category</div>
                </div>

                <!-- Slug (Auto-generated) -->
                <div class="form-floating mb-3">
                    <input type="text"
                        class="form-control"
                        id="slug"
                        name="slug"
                        placeholder="Slug"
                        value="<?= old('slug', $category->slug ?? '') ?>"
                        required>
                    <label for="slug">Slug <span class="text-danger">*</span></label>
                    <div class="form-text">URL-friendly version (auto-generated from name)</div>
                </div>

                <!-- Description -->
                <div class="form-floating mb-4">
                    <textarea class="form-control"
                        id="description"
                        name="description"
                        placeholder="Description"
                        style="height: 100px"><?= old('description', $category->description ?? '') ?></textarea>
                    <label for="description">Description (Optional)</label>
                    <div class="form-text">Brief description of this category</div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2">
                    <button type="submit" id="save-category" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Save Category
                    </button>
                    <a href="<?= url_to('admin.affiliate.categories') ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Auto-generate slug from name
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    // Only auto-generate on create (not edit)
    <?php if (!$category): ?>
        nameInput.addEventListener('input', function() {
            const slug = this.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            slugInput.value = slug;
        });
    <?php endif; ?>
</script>
<?= $this->endSection() ?>