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

}
