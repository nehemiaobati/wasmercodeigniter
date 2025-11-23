<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .content-block {
        border: 1px solid var(--border-color);
        border-radius: 0.5rem;
        position: relative;
        background-color: var(--bs-tertiary-bg);
    }

    .block-controls {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        display: flex;
        gap: 0.25rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= url_to('admin.blog.index') ?>" class="btn btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i> Back</a>
        <h1 class="fw-bold mb-0"><?= esc($formTitle) ?></h1>
    </div>

    <div class="card blueprint-card">
        <div class="card-body p-4 p-md-5">
            <form action="<?= esc($formAction) ?>" method="post">
                <?= csrf_field() ?>
                <div class="row g-4">
                    <div class="col-md-8">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="title" name="title" placeholder="Post Title" value="<?= old('title', $post->title ?? '') ?>" required>
                            <label for="title">Post Title</label>
                        </div>

                        <h5 class="fw-bold">Content Builder</h5>
                        <div id="content-builder-area" class="d-flex flex-column gap-3 mb-3">
                            <?php
                            $contentBlocks = old('content_type') ? [] : (isset($post->body_content) ? $post->body_content : []);
                            if (old('content_type')) {
                                foreach (old('content_type') as $index => $type) {
                                    $block = ['type' => $type];
                                    if ($type === 'text' || $type === 'image') $block['content'] = old('content_text')[$index];
                                    if ($type === 'code') {
                                        $block['code'] = old('content_text')[$index];
                                        $block['language'] = old('content_language')[$index];
                                    }
                                    $contentBlocks[] = (object)$block;
                                }
                            }
                            ?>
                            <?php if (!empty($contentBlocks)): ?>
                                <?php foreach ($contentBlocks as $block): ?>
                                    <?php if ($block->type === 'text'): ?>
                                        <div class="content-block p-3 pt-5">
                                            <input type="hidden" name="content_type[]" value="text">
                                            <div class="block-controls"><button type="button" class="btn btn-sm btn-outline-danger remove-block"><i class="bi bi-trash"></i></button></div>
                                            <textarea name="content_text[]" class="form-control" rows="8" placeholder="Enter your text content (Markdown supported)"><?= esc($block->content ?? '') ?></textarea>
                                            <input type="hidden" name="content_language[]" value="">
                                        </div>
                                    <?php elseif ($block->type === 'image'): ?>
                                        <div class="content-block p-3 pt-5">
                                            <input type="hidden" name="content_type[]" value="image">
                                            <div class="block-controls"><button type="button" class="btn btn-sm btn-outline-danger remove-block"><i class="bi bi-trash"></i></button></div>
                                            <input type="text" name="content_text[]" class="form-control" placeholder="Enter Image URL" value="<?= esc($block->url ?? ($block->content ?? '')) ?>">
                                            <input type="hidden" name="content_language[]" value="">
                                        </div>
                                    <?php elseif ($block->type === 'code'): ?>
                                        <div class="content-block p-3 pt-5">
                                            <input type="hidden" name="content_type[]" value="code">
                                            <div class="block-controls"><button type="button" class="btn btn-sm btn-outline-danger remove-block"><i class="bi bi-trash"></i></button></div>
                                            <textarea name="content_text[]" class="form-control" rows="8" placeholder="Enter code snippet"><?= esc($block->code ?? '') ?></textarea>
                                            <input type="text" name="content_language[]" class="form-control mt-2" placeholder="Language (e.g., php, javascript)" value="<?= esc($block->language ?? '') ?>">
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary" id="add-text-block"><i class="bi bi-fonts"></i> Add Text</button>
                            <button type="button" class="btn btn-outline-secondary" id="add-image-block"><i class="bi bi-image"></i> Add Image</button>
                            <button type="button" class="btn btn-outline-secondary" id="add-code-block"><i class="bi bi-code-slash"></i> Add Code</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="status" name="status">
                                <option value="published" <?= old('status', $post->status ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= old('status', $post->status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            </select>
                            <label for="status">Status</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="datetime-local" class="form-control" id="published_at" name="published_at" value="<?= old('published_at', ($post->published_at ?? null) ? \CodeIgniter\I18n\Time::parse($post->published_at)->format('Y-m-d\TH:i') : date('Y-m-d\TH:i')) ?>">
                            <label for="published_at">Publish Date</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="featured_image_url" name="featured_image_url" placeholder="Image URL" value="<?= old('featured_image_url', ($post ? $post->featured_image_url : '')) ?>">
                            <label for="featured_image_url">Featured Image URL</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="category_name" name="category_name" placeholder="Category" value="<?= old('category_name', $post->category_name ?? '') ?>">
                            <label for="category_name">Category</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="author_name" name="author_name" placeholder="Author Name" value="<?= old('author_name', $post->author_name ?? '') ?>">
                            <label for="author_name">Author Name</label>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="excerpt" name="excerpt" placeholder="Short summary..." style="height: 100px"><?= old('excerpt', $post->excerpt ?? '') ?></textarea>
                            <label for="excerpt">Excerpt (Short Summary)</label>
                        </div>
                        <div class="form-floating">
                            <textarea class="form-control" id="meta_description" name="meta_description" placeholder="SEO Description..." style="height: 100px"><?= old('meta_description', $post->meta_description ?? '') ?></textarea>
                            <label for="meta_description">Meta Description (for SEO)</label>
                        </div>
                    </div>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Save Post</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const builderArea = document.getElementById('content-builder-area');

        const createBlock = (type) => {
            const block = document.createElement('div');
            block.className = 'content-block p-3 pt-5';
            let innerHTML = `<input type="hidden" name="content_type[]" value="${type}"><div class="block-controls"><button type="button" class="btn btn-sm btn-outline-danger remove-block"><i class="bi bi-trash"></i></button></div>`;
            if (type === 'text') {
                innerHTML += `<textarea name="content_text[]" class="form-control" rows="8" placeholder="Enter your text content (Markdown supported)"></textarea><input type="hidden" name="content_language[]" value="">`;
            } else if (type === 'image') {
                innerHTML += `<input type="text" name="content_text[]" class="form-control" placeholder="Enter Image URL"><input type="hidden" name="content_language[]" value="">`;
            } else if (type === 'code') {
                innerHTML += `<textarea name="content_text[]" class="form-control" rows="8" placeholder="Enter code snippet"></textarea><input type="text" name="content_language[]" class="form-control mt-2" placeholder="Language (e.g., php, javascript)">`;
            }
            block.innerHTML = innerHTML;
            builderArea.appendChild(block);
        };

        document.getElementById('add-text-block').addEventListener('click', () => createBlock('text'));
        document.getElementById('add-image-block').addEventListener('click', () => createBlock('image'));
        document.getElementById('add-code-block').addEventListener('click', () => createBlock('code'));

        builderArea.addEventListener('click', (e) => {
            if (e.target.closest('.remove-block')) {
                e.target.closest('.content-block').remove();
            }
        });
    });
</script>
<?= $this->endSection() ?>