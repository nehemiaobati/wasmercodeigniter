<?php declare(strict_types=1);

namespace App\Modules\Blog\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property ?string $excerpt
 * @property ?array $body_content  // This will now be an array of objects
 * @property ?string $featured_image_url
 * @property string $author_name
 * @property ?string $category_name
 * @property ?string $meta_description
 * @property string $status
 * @property ?string $published_at
 * @property string $created_at
 * @property string $updated_at
 */
class Post extends Entity
{
    protected $dates   = ['published_at', 'created_at', 'updated_at'];
    protected $casts   = [
        'id' => 'integer',
        
        // FIX: Changed from 'json-array' to 'json'.
        // 'json' decodes JSON into an array of objects (if the JSON is an array of objects).
        // 'json-array' decodes JSON into an array of associative arrays.
        'body_content' => 'json',
    ];
}
