<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class DocumentationController extends BaseController
{
    public function index(): string
    {
        $data = [
            'pageTitle'       => 'Platform Documentation | User Guides & Resources',
            'metaDescription' => 'Learn how to use the GenAI Web Platform to generate content, analyze documents, and access crypto market insights without writing code.',
            'canonicalUrl'    => url_to('documentation'), // Ensure route name exists
            'robotsTag'       => 'index, follow', // CHANGED: Allow indexing
        ];
        return view('documentation/index', $data);
    }

    public function web(): string
    {
        $data = [
            'pageTitle'       => 'Web Platform Guide |  Architecture',
            'metaDescription' => 'Detailed overview of the platform architecture and features for users and administrators.',
            'canonicalUrl'    => url_to('web'),
            'robotsTag'       => 'index, follow', // CHANGED
        ];
        return view('documentation/web_documentation', $data);
    }

    public function agi(): string
    {
        $data = [
            'pageTitle'       => 'AI Tools Guide | Architecture',
            'metaDescription' => 'How to leverage our Gemini-powered AI tools for text generation, document analysis, and TTS solutions.',
            'canonicalUrl'    => url_to('agi'),
            'robotsTag'       => 'index, follow', // CHANGED
        ];
        return view('documentation/agi_documentation', $data);
    }
}
