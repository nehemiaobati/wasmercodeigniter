<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<div class="container my-5">
    <div class="blueprint-header d-flex justify-content-between align-items-center mb-4">
        <h1><?= esc($pageTitle) ?></h1>
        <a href="<?= url_to('admin.affiliate.create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add New Link
        </a>
    </div>

    <div class="card blueprint-card">
        <div class="card-body">
            <?php if (empty($links)): ?>
                <p class="text-muted text-center py-4">No affiliate links found. Create your first one to get started.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Code</th>
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
                                        <?= $link->title ? esc($link->title) : '<em class="text-muted">No title</em>' ?>
                                    </td>
                                    <td>
                                        <code><?= esc($link->code) ?></code>
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
                                        <a href="<?= url_to('admin.affiliate.edit', $link->id) ?>"
                                            class="btn btn-sm btn-outline-primary me-1">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="<?= url_to('admin.affiliate.delete', $link->id) ?>"
                                            method="post"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this affiliate link?');">
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
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function() {
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
</script>
<?= $this->endSection() ?>