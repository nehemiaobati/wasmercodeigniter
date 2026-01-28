<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<div class="container my-5">
    <div class="blueprint-header d-flex justify-content-between align-items-center mb-4">
        <h1><?= esc($pageTitle) ?></h1>
        <div>
            <a href="<?= url_to('admin.affiliate.index') ?>" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left me-2"></i>Back to Links
            </a>
            <a href="<?= url_to('admin.affiliate.category.create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add New Category
            </a>
        </div>
    </div>

    <div class="card blueprint-card">
        <div class="card-body">
            <?php if (session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= session('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (session('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= session('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($categories)): ?>
                <p class="text-muted text-center py-4">No categories found. Create your first one to organize your affiliate links.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th class="text-center">Links</th>
                                <th>Description</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><strong><?= esc($category->name) ?></strong></td>
                                    <td><code><?= esc($category->slug) ?></code></td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= esc($category->link_count ?? 0) ?></span>
                                    </td>
                                    <td>
                                        <?= $category->description ? esc($category->description) : '<em class="text-muted">No description</em>' ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= url_to('admin.affiliate.category.edit', $category->id) ?>"
                                            class="btn btn-sm btn-outline-primary me-1">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="<?= url_to('admin.affiliate.category.delete', $category->id) ?>"
                                            method="post"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure? Links in this category will be set to \'No Category\'.');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>