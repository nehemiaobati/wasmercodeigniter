<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold">Manage Blog Posts</h1>
        <a href="<?= url_to('admin.blog.create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Post</a>
    </div>

    <div class="card blueprint-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="fw-semibold">Title</th>
                        <th class="fw-semibold">Status</th>
                        <th class="fw-semibold">Published Date</th>
                        <th class="fw-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><strong><?= esc($post->title) ?></strong></td>
                                <td>
                                    <span class="badge bg-<?= $post->status === 'published' ? 'success' : 'secondary' ?>">
                                        <?= esc(ucfirst($post->status)) ?>
                                    </span>
                                </td>
                                <td><?= $post->published_at ? $post->published_at->toFormattedDateString() : 'Not Set' ?></td>
                                <td>
                                    <a href="<?= url_to('blog.show', $post->slug) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="View Post"><i class="bi bi-eye"></i></a>
                                    <a href="<?= url_to('admin.blog.edit', $post->id) ?>" class="btn btn-sm btn-outline-primary" title="Edit Post"><i class="bi bi-pencil"></i></a>
                                    <form action="<?= url_to('admin.blog.delete', $post->id) ?>" method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Post" onclick="return confirm('Are you sure you want to delete this post?');">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">No posts found. <a href="<?= url_to('admin.blog.create') ?>">Create one now</a>.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex justify-content-center mt-4">
        <?= $pager->links() ?>
    </div>
</div>
<?= $this->endSection() ?>
