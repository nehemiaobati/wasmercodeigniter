<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<div class="container my-5">
    <div class="blueprint-header d-flex justify-content-between align-items-center mb-4">
        <h1><?= esc($pageTitle) ?></h1>
        <div>
            <a href="<?= url_to('admin.affiliate.categories') ?>" class="btn btn-outline-secondary me-2">
                <i class="bi bi-folder me-2"></i>Manage Categories
            </a>
            <a href="<?= url_to('admin.affiliate.create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add New Link
            </a>
        </div>
    </div>

    <div class="card blueprint-card">
        <div class="card-body">
            <!-- Search and Filter Form -->
            <form method="get" action="<?= url_to('admin.affiliate.index') ?>" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search by title or code..."
                            value="<?= esc($filters['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat->id ?>" <?= ($filters['category'] ?? '') == $cat->id ? 'selected' : '' ?>>
                                    <?= esc($cat->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" id="filter-submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </div>
                <?php if ($filters['search'] || $filters['status'] || $filters['category']): ?>
                    <div class="mt-2">
                        <a href="<?= url_to('admin.affiliate.index') ?>" class="btn btn-sm btn-link">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </form>

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

            <?php if (empty($links)): ?>
                <p class="text-muted text-center py-4">No affiliate links found. Create your first one to get started.</p>
            <?php else: ?>
                <!-- Bulk Action Form -->
                <form id="bulkActionForm" method="post" action="<?= url_to('admin.affiliate.bulk') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" id="bulkAction">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div id="bulkActionsBar" style="display: none;">
                            <span class="me-2"><span id="selectedCount">0</span> selected</span>
                            <button type="button" id="bulk-activate" class="btn btn-sm btn-success" onclick="submitBulkAction('activate')">
                                <i class="bi bi-check-circle"></i> Activate
                            </button>
                            <button type="button" id="bulk-deactivate" class="btn btn-sm btn-warning" onclick="submitBulkAction('deactivate')">
                                <i class="bi bi-x-circle"></i> Deactivate
                            </button>
                            <button type="button" id="bulk-delete" class="btn btn-sm btn-danger" onclick="submitBulkAction('delete')">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 30px;">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Title</th>
                                    <th>Code</th>
                                    <th>Category</th>
                                    <th>Clean URL</th>
                                    <th class="text-center">Clicks</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($links as $link): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="ids[]" value="<?= $link->id ?>" class="form-check-input link-checkbox">
                                        </td>
                                        <td>
                                            <?= $link->title ? esc($link->title) : '<em class="text-muted">No title</em>' ?>
                                        </td>
                                        <td>
                                            <code><?= esc($link->code) ?></code>
                                        </td>
                                        <td>
                                            <?php if ($link->category_id): ?>
                                                <?php
                                                $linkCategory = array_filter($categories, fn($c) => $c->id == $link->category_id);
                                                $linkCategory = reset($linkCategory);
                                                ?>
                                                <?php if ($linkCategory): ?>
                                                    <span class="badge bg-secondary"><?= esc($linkCategory->name) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <code class="text-truncate" style="max-width: 200px;"><?= base_url('amazon/' . $link->code) ?></code>
                                                <button class="btn btn-sm btn-link p-0 copy-btn"
                                                    data-url="<?= base_url('amazon/' . $link->code) ?>"
                                                    title="Copy link">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                Original: <a href="<?= esc($link->short_url) ?>" target="_blank" class="text-decoration-none opacity-75"><?= esc($link->short_url) ?></a>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?= esc($link->click_count) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($link->status === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url_to('admin.affiliate.analytics', $link->id) ?>"
                                                class="btn btn-sm btn-outline-info me-1"
                                                title="View Analytics">
                                                <i class="bi bi-graph-up"></i>
                                            </a>
                                            <a href="<?= url_to('admin.affiliate.edit', $link->id) ?>"
                                                class="btn btn-sm btn-outline-primary me-1">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="<?= url_to('admin.affiliate.delete', $link->id) ?>"
                                                method="post"
                                                class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this affiliate link?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" id="delete-link-<?= $link->id ?>" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <div class="mt-4 d-flex justify-content-center">
                    <?= $pager->links() ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Copy to clipboard functionality
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('data-url');
            const icon = this.querySelector('i');

            navigator.clipboard.writeText(url).then(() => {
                // Success feedback
                icon.classList.replace('bi-clipboard', 'bi-check-lg');
                icon.classList.add('text-success');

                setTimeout(() => {
                    icon.classList.replace('bi-check-lg', 'bi-clipboard');
                    icon.classList.remove('text-success');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        });
    });

    // Bulk selection functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const linkCheckboxes = document.querySelectorAll('.link-checkbox');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCountSpan = document.getElementById('selectedCount');

    function updateSelectedCount() {
        const count = document.querySelectorAll('.link-checkbox:checked').length;
        selectedCountSpan.textContent = count;
        bulkActionsBar.style.display = count > 0 ? 'block' : 'none';
    }

    selectAllCheckbox.addEventListener('change', function() {
        linkCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedCount();
    });

    linkCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    function submitBulkAction(action) {
        const selectedCount = document.querySelectorAll('.link-checkbox:checked').length;

        if (selectedCount === 0) {
            alert('Please select at least one link.');
            return;
        }

        let confirmMessage = `Are you sure you want to ${action} ${selectedCount} link(s)?`;

        if (confirm(confirmMessage)) {
            document.getElementById('bulkAction').value = action;
            document.getElementById('bulkActionForm').submit();
        }
    }
</script>
<?= $this->endSection() ?>