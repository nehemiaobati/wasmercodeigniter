<?php

declare(strict_types=1);

namespace App\Modules\Blog\Libraries;

use App\Modules\Blog\Entities\Post;
use App\Modules\Blog\Models\PostModel;

class BlogService
{
    protected PostModel $postModel;

    public function __construct(?PostModel $postModel = null)
    {
        $this->postModel = $postModel ?? new PostModel();
    }

    /**
     * Creates a new post.
     *
     * @param array $data Raw POST data
     * @return bool
     */
    public function createPost(array $data): bool
    {
        $payload = $this->preparePayload($data);
        return (bool) $this->postModel->save($payload);
    }

    /**
     * Updates an existing post.
     *
     * @param int $id Post ID
     * @param array $data Raw POST data
     * @return bool
     */
    public function updatePost(int $id, array $data): bool
    {
        $payload = $this->preparePayload($data);
        $payload['id'] = $id;

        return (bool) $this->postModel->save($payload);
    }

    /**
     * Deletes a post.
     *
     * @param int $id Post ID
     * @return bool
     */
    public function deletePost(int $id): bool
    {
        return (bool) $this->postModel->delete($id);
    }

    /**
     * Returns the validation errors from the model.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->postModel->errors();
    }

    /**
     * Prepares the data payload for saving.
     *
     * @param array $data
     * @return array
     */
    protected function preparePayload(array $data): array
    {
        return [
            'title'              => $data['title'] ?? null,
            'excerpt'            => $data['excerpt'] ?? null,
            'status'             => $data['status'] ?? 'draft',
            'published_at'       => !empty($data['published_at']) ? str_replace('T', ' ', $data['published_at']) . ':00' : null,
            'featured_image_url' => $data['featured_image_url'] ?? null,
            'category_name'      => $data['category_name'] ?? null,
            'author_name'        => $data['author_name'] ?? 'Nehemia Obati',
            'meta_description'   => $data['meta_description'] ?? null,
            'body_content'       => $this->processContentBlocks($data)
        ];
    }

    /**
     * Processes content blocks from the request into a JSON string.
     *
     * @param array $data
     * @return string JSON encoded string
     */
    public function processContentBlocks(array $data): string
    {
        $contentBlocks = [];
        $contentTypes = $data['content_type'] ?? [];

        if (is_array($contentTypes)) {
            $contentText = $data['content_text'] ?? [];
            $contentLang = $data['content_language'] ?? [];

            foreach ($contentTypes as $index => $type) {
                $block = ['type' => $type];
                $text = $contentText[$index] ?? null;
                $language = $contentLang[$index] ?? null;

                if ($text !== null && trim($text) !== '') {
                    switch ($type) {
                        case 'text':
                            $block['content'] = $text;
                            $contentBlocks[] = $block;
                            break;
                        case 'image':
                            $block['url'] = $text;
                            $contentBlocks[] = $block;
                            break;
                        case 'code':
                            $block['code'] = $text;
                            $block['language'] = !empty($language) ? $language : 'plaintext';
                            $contentBlocks[] = $block;
                            break;
                    }
                }
            }
        }

        return json_encode($contentBlocks);
    }
}
