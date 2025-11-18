<?php declare(strict_types=1);

namespace App\Modules\Blog\Models;

use CodeIgniter\Model;
use App\Modules\Blog\Entities\Post;

class PostModel extends Model
{
    protected $table            = 'posts';
    protected $primaryKey       = 'id';
    protected $returnType       = Post::class;
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'title', 'slug', 'excerpt', 'body_content', 'featured_image_url',
        'author_name', 'category_name', 'meta_description', 'status', 'published_at'
    ];

    protected $validationRules = [
        'title'   => 'required|min_length[5]|max_length[255]',
        'excerpt' => 'permit_empty|max_length[500]',
        'status'  => 'required|in_list[published,draft]',
    ];

    protected $beforeInsert = ['generateSlug'];
    protected $beforeUpdate = ['generateSlug'];

    protected function generateSlug(array $data): array
    {
        if (isset($data['data']['title'])) {
            $slug = url_title($data['data']['title'], '-', true);

            // Safely get the ID for an update operation. $data['id'] is an array.
            $id = $data['id'][0] ?? null;

            // --- FIX STARTS HERE ---
            // The uniqueness check must run for both inserts and updates.
            $builder = $this->builder();
            $builder->where('slug', $slug);

            // If we are updating a post, we must exclude its own ID from the check.
            if ($id !== null) {
                $builder->where('id !=', $id);
            }

            // Execute the query.
            $existing = $builder->get()->getRow();

            // If a post with the same slug exists, append a unique identifier.
            if ($existing) {
                $slug .= '-' . uniqid();
            }
            // --- FIX ENDS HERE ---
            
            $data['data']['slug'] = $slug;
        }
        return $data;
    }
}
