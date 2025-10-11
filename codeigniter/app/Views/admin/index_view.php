<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .stat-card {
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
        border: none;
    }
    .stat-card .card-body {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
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
    .stat-card .stat-value {
        font-size: 2rem;
        font-weight: 700;
    }
    .table-wrapper {
        border-radius: 0.75rem;
        overflow: hidden;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
    }
    .table thead {
        background-color: var(--bs-light);
    }
    .table th {
        font-weight: 600;
    }
    .pagination .page-item .page-link {
        color: var(--primary-color);
    }
    /* Improved Pagination Styling */
    .pagination {
        --bs-pagination-padding-x: 0.85rem;
        --bs-pagination-padding-y: 0.45rem;
        --bs-pagination-font-size: 0.95rem;
        --bs-pagination-border-width: 0;
        --bs-pagination-border-radius: 0.375rem;
        --bs-pagination-hover-color: var(--primary-color);
        --bs-pagination-hover-bg: #e9ecef;
        --bs-pagination-active-color: #fff;
        --bs-pagination-active-bg: var(--primary-color);
        --bs-pagination-disabled-color: #6c757d;
        --bs-pagination-disabled-bg: #fff;
    }
    .pagination .page-item {
        margin: 0 4px; /* Adds space between page items */
    }
    .pagination .page-link {
        border-radius: var(--bs-pagination-border-radius) !important; /* Ensure consistent border radius */
        transition: all 0.2s ease-in-out;
    }
    .pagination .page-item.active .page-link {
        box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
        transform: translateY(-2px);
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

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="icon"><i class="bi bi-wallet2"></i></div>
                                <div>
                                    <h6 class="card-subtitle text-muted">Total User Balance</h6>
                                    <p class="card-text stat-value">Ksh. <?= number_format($total_balance, 2) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
        <div class="col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="icon"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <h6 class="card-subtitle text-muted">Total Users</h6>
                        <p class="card-text stat-value"><?= $total_users ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Management Table -->
    <h2 class="h3 fw-bold mb-3">User Management</h2>
    <div class="card table-wrapper">
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
