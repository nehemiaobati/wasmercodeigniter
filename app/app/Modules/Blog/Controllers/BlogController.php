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
            // SEO: Strong title focusing on value proposition
            'pageTitle'       => 'Tech Insights & Tutorials | Afrikenkid Blog',
            // SEO: Clear description with keywords for the target market
            'metaDescription' => 'Explore articles on fintech, software development, AI, and consumer tech tailored for the Kenyan and African market.',
            'canonicalUrl'    => url_to('blog.index'),
            'posts'           => $this->postModel->where('status', 'published')->orderBy('published_at', 'DESC')->paginate(6),
            'pager'           => $this->postModel->pager,
            // SEO: Allow indexing for the blog listing
            'robotsTag'       => 'index, follow', 
        ];
        return view('App\Modules\Blog\Views\blog\index', $data);
    }

    public function show(string $slug): string
    {
        // Allow admins to preview drafts, otherwise only find published posts
        $post = $this->postModel->where('slug', $slug)->first();

        // SEO/UX: If not found, or if it's a draft and user isn't admin, 404
        if (!$post || ($post->status !== 'published' && !session()->get('is_admin'))) {
            throw PageNotFoundException::forPageNotFound('The requested blog post was not found or is not published.');
        }

        // SEO: Structured Data (JSON-LD) for Rich Results
        $schema = [
            "@context"        => "https://schema.org",
            "@type"           => "BlogPosting",
            "headline"        => $post->title,
            "image"           => $post->featured_image_url ? [ $post->featured_image_url ] : [], // Handle missing images gracefully
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
            'robotsTag'       => 'index, follow',
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
            'robotsTag' => 'noindex, nofollow', // SEO: Keep admin pages out of search
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
        
        // Validation: Ensure we actually have content types to process
        $contentTypes = $this->request->getPost('content_type');
        
        if (is_array($contentTypes)) {
            // Fetch arrays once to avoid repeated calls
            $contentText = $this->request->getPost('content_text');
            $contentLang = $this->request->getPost('content_language');

            foreach ($contentTypes as $index => $type) {
                $block = ['type' => $type];
                $text = $contentText[$index] ?? null;
                $language = $contentLang[$index] ?? null;

                // Logic: Only add valid blocks with actual content
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
        
        $payload = [
            'title'              => $this->request->getPost('title'),
            'excerpt'            => $this->request->getPost('excerpt'),
            'status'             => $this->request->getPost('status'),
            'published_at'       => $this->request->getPost('published_at'),
            'featured_image_url' => $this->request->getPost('featured_image_url'),
            'category_name'      => $this->request->getPost('category_name'),
            'meta_description'   => $this->request->getPost('meta_description'),
            'body_content'       => json_encode($contentBlocks)
        ];

        if ($id !== null) {
            $payload['id'] = $id;
        }

        if ($this->postModel->save($payload)) {
            return redirect()->to(url_to('admin.blog.index'))->with('success', 'Post ' . ($id ? 'updated' : 'created') . ' successfully.');
        }
        
        return redirect()->back()->withInput()->with('errors', $this->postModel->errors());
    }

    public function delete(int $id)
    {
        if (!session()->get('is_admin')) { return redirect()->to(url_to('home')); }
        
        $post = $this->postModel->find($id);
        if (!$post) {
            throw PageNotFoundException::forPageNotFound('Cannot delete a post that does not exist.');
        }

        if ($this->postModel->delete($id)) {
            return redirect()->to(url_to('admin.blog.index'))->with('success', 'Post deleted successfully.');
        }
        
        return redirect()->to(url_to('admin.blog.index'))->with('error', 'Failed to delete the post due to a server error.');
    }
}