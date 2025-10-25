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
        return view('documentation/index');
    }
}
