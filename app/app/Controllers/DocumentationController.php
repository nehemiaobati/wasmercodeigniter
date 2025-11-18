<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class DocumentationController extends BaseController
{
    /**
     * Displays the documentation page.
     *
     * @return ResponseInterface
     */
    public function index(): string
    {
        // Set robotsTag to 'noindex, follow' for documentation pages.
        $data['robotsTag'] = 'noindex, follow';
        return view('documentation/index', $data);
    }

    public function web(): string
    {
        // Set robotsTag to 'noindex, follow' for documentation pages.
        $data['robotsTag'] = 'noindex, follow';
        return view('documentation/web_documentation', $data);
    }

    public function agi(): string
    {
        // Set robotsTag to 'noindex, follow' for documentation pages.
        $data['robotsTag'] = 'noindex, follow';
        return view('documentation/agi_documentation', $data);
    }
}
