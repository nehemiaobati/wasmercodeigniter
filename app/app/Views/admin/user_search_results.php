<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .table thead {
        background-color: var(--bs-light);
    }
    .table th {
        font-weight: 600;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold">Search Results for: "<?= esc($search_query) ?>"</h1>
        <a href="<?= url_to('admin.index') ?>" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- User Management Table -->
    <h2 class="h3 fw-bold mb-3">Users Found</h2>
    <div class="card blueprint-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Username</th>
                        <th scope="col">Email</th>
                        <th scope="col">Balance</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?= esc($user->username) ?></strong></td>
                                <td><?= esc($user->email) ?></td>
                                <td>Ksh. <?= number_format($user->balance, 2) ?></td>
                                <td>
                                    <a href="<?= url_to('admin.users.show', $user->id) ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                    <form action="<?= url_to('admin.users.delete', $user->id) ?>" method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No users found matching your search criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (isset($pager) && $pager->getPageCount() > 1): ?>
        <div class="d-flex justify-content-center mt-4">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
