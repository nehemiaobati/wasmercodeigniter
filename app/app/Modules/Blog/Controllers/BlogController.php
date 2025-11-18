<?php declare(strict_types=1);

namespace App\Modules\Blog\Controllers;

use App\Controllers\BaseController;
use App\Modules\Blog\Models\PostModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class BlogController extends BaseController
{
    protected PostModel $postModel;

    public function __construct()
    {
        $this->postModel = new PostModel();
        helper('form');
    }

    // --- PUBLIC-FACING METHODS ---

    public function index(): string
    {
        $data = [
            'pageTitle'       => 'Tech Insights & Tutorials | Afrikenkid Blog',
            'metaDescription' => 'Explore articles on fintech, software development, AI, and consumer tech tailored for the Kenyan and African market.',
            'canonicalUrl'    => url_to('blog.index'),
            'posts'           => $this->postModel->where('status', 'published')->orderBy('published_at', 'DESC')->paginate(6),
            'pager'           => $this->postModel->pager,
        ];
        return view('App\Modules\Blog\Views\blog\index', $data);
    }

    public function show(string $slug): string
    {
        // IMPROVEMENT: Find post regardless of status to allow admin preview.
        $post = $this->postModel->where('slug', $slug)->first();

        // IMPROVEMENT: More robust check for visibility.
        if (!$post || ($post->status !== 'published' && !session()->get('is_admin'))) {
            throw PageNotFoundException::forPageNotFound('The requested blog post was not found or is not published.');
        }

        $schema = [
            "@context"        => "https://schema.org",
            "@type"           => "BlogPosting",
            "headline"        => $post->title,
            "image"           => $post->featured_image_url,
            "datePublished"   => $post->published_at ? $post->published_at->toDateTimeString() : null,
            "dateModified"    => $post->updated_at ? $post->updated_at->toDateTimeString() : null,
            "author"          => [ "@type" => "Person", "name"  => $post->author_name ],
            "publisher"       => [
                "@type" => "Organization", "name"  => "Afrikenkid",
                "logo"  => [ "@type" => "ImageObject", "url"   => base_url('assets/images/logo.png') ],
            ],
            "description"     => $post->meta_description,
            "mainEntityOfPage" => [ "@type" => "WebPage", "@id"   => url_to('blog.show', $slug) ],
        ];

        $data = [
            'pageTitle'       => esc($post->title) . ' | Afrikenkid Blog',
            'metaDescription' => esc($post->meta_description),
            'canonicalUrl'    => url_to('blog.show', $slug),
            'post'            => $post,
            'json_ld_schema'  => '<script type="application/ld+json">' . json_encode($schema) . '</script>',
        ];
        return view('App\Modules\Blog\Views\blog\post', $data);
    }

    // --- ADMIN-ONLY METHODS ---

    public function adminIndex()
    {
        if (!session()->get('is_admin')) { return redirect()->to(url_to('home')); }
        $data = [
            'pageTitle' => 'Manage Blog Posts | Admin',
            'posts'     => $this->postModel->orderBy('created_at', 'DESC')->paginate(10),
            'pager'     => $this->postModel->pager,
            'robotsTag' => 'noindex, nofollow',
        ];
        return view('App\Modules\Blog\Views\admin\blog\index', $data);
    }

    public function create()
    {
        if (!session()->get('is_admin')) { return redirect()->to(url_to('home')); }
        $data = [
            'pageTitle'  => 'Create New Post | Admin',
            'formTitle'  => 'Create New Post',
            'formAction' => url_to('admin.blog.store'),
            'post'       => null,
            'robotsTag'  => 'noindex, nofollow',
        ];
        return view('App\Modules\Blog\Views\admin\blog\form', $data);
    }

    public function edit(int $id)
    {
        if (!session()->get('is_admin')) { return redirect()->to(url_to('home')); }
        $post = $this->postModel->find($id);
        if (!$post) { throw PageNotFoundException::forPageNotFound(); }
        $data = [
            'pageTitle'  => 'Edit Post | Admin',
            'formTitle'  => 'Edit Post: ' . esc($post->title),
            'formAction' => url_to('admin.blog.update', $id),
            'post'       => $post,
            'robotsTag'  => 'noindex, nofollow',
        ];
        return view('App\Modules\Blog\Views\admin\blog\form', $data);
    }

    public function store()
    {
        if (!session()->get('is_admin')) { return redirect()->to(url_to('home')); }
        return $this->processPost();
    }

    public function update(int $id)
    {
        if (!session()->get('is_admin')) { return redirect()->to(url_to('home')); }
        return $this->processPost($id);
    }

    private function processPost(?int $id = null)
    {
        $contentBlocks = [];
        // IMPROVEMENT: Check that content_type is an array to prevent errors.
        $contentTypes = $this->request->getPost('content_type');
        
        if (is_array($contentTypes)) {
            foreach ($contentTypes as $index => $type) {
                $block = ['type' => $type];
                $text = $this->request->getPost('content_text')[$index] ?? null;
                $language = $this->request->getPost('content_language')[$index] ?? null;

                // IMPROVEMENT: Only add block if it has content.
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
        
        // IMPROVEMENT: Use request->getPost() for safer data retrieval.
        $payload = [
            'title'              => $this->request->getPost('title', FILTER_SANITIZE_STRING),
            'excerpt'            => $this->request->getPost('excerpt', FILTER_SANITIZE_STRING),
            'status'             => $this->request->getPost('status'),
            'published_at'       => $this->request->getPost('published_at'),
            'featured_image_url' => $this->request->getPost('featured_image_url', FILTER_SANITIZE_URL),
            'category_name'      => $this->request->getPost('category_name', FILTER_SANITIZE_STRING),
            'meta_description'   => $this->request->getPost('meta_description', FILTER_SANITIZE_STRING),
            'body_content'       => json_encode($contentBlocks)
        ];

        if ($id !== null) {
            $payload['id'] = $id;
        }

        if ($this->postModel->save($payload)) {
            return redirect()->to(url_to('admin.blog.index'))->with('success', 'Post ' . ($id ? 'updated' : 'created') . ' successfully.');
        }
        
        // On failure, pass the model's errors back to the view.
        return redirect()->back()->withInput()->with('errors', $this->postModel->errors());
    }

    public function delete(int $id)
    {
        if (!session()->get('is_admin')) { return redirect()->to(url_to('home')); }
        
        // IMPROVEMENT: Check if post exists before attempting deletion.
        $post = $this->postModel->find($id);
        if (!$post) {
            throw PageNotFoundException::forPageNotFound('Cannot delete a post that does not exist.');
        }

        if ($this->postModel->delete($id)) {
            return redirect()->to(url_to('admin.blog.index'))->with('success', 'Post deleted successfully.');
        }
        
        // This case would typically only be hit if a database-level error occurs.
        return redirect()->to(url_to('admin.blog.index'))->with('error', 'Failed to delete the post due to a server error.');
    }
}