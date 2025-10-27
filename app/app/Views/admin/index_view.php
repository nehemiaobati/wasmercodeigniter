<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .stat-card .icon {
        font-size: 2.5rem;
        padding: 1rem;
        background-color: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
        border-radius: 50%;
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    a.action-card {
        text-decoration: none;
        color: inherit;
        display: block;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold">Admin Dashboard</h1>
        <form action="<?= url_to('admin.users.search') ?>" method="GET" class="d-flex">
            <input type="text" name="q" class="form-control me-2" placeholder="Search users..." value="<?= esc($search_query ?? '') ?>">
            <button type="submit" class="btn btn-outline-primary">Search</button>
        </form>
    </div>

    <!-- Stats & Actions Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card blueprint-card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon"><i class="bi bi-wallet2"></i></div>
                    <div>
                        <h6 class="card-subtitle text-muted">Total User Balance</h6>
                        <p class="card-text stat-value fs-2 fw-bold">Ksh. <?= number_format($total_balance, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card blueprint-card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <h6 class="card-subtitle text-muted">Total Users</h6>
                        <p class="card-text stat-value fs-2 fw-bold"><?= $total_users ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <a href="<?= url_to('admin.campaign.create') ?>" class="action-card">
                <div class="card blueprint-card stat-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="icon"><i class="bi bi-envelope-paper-heart"></i></div>
                        <div>
                            <h6 class="card-subtitle text-muted">Engage Users</h6>
                            <p class="card-text stat-value fs-2 fw-bold h2">Send Campaign</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <!-- User Management Table -->
    <h2 class="h3 fw-bold mb-3">User Management</h2>
    <div class="card blueprint-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="fw-semibold">Username</th>
                        <th class="fw-semibold">Email</th>
                        <th class="fw-semibold">Balance</th>
                        <th class="fw-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
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
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        <?= $pager->links() ?>
    </div>
</div>
<?= $this->endSection() ?>
