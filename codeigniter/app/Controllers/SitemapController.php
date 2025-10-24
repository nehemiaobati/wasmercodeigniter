<?php declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class SitemapController extends BaseController
{
    /**
     * Generates the sitemap.xml file by rendering a view.
     */
    public function index(): ResponseInterface
    {
        // Array of public-facing, static pages by their route name
        $pages = [
            'welcome', 'register', 'login', 'contact.form',
            'portfolio.index', 'terms', 'privacy',
        ];

        $urls = [];
        $today = date('Y-m-d');

        foreach ($pages as $page) {
            $generatedUrl = url_to($page);

            if ($generatedUrl) {
                $urls[] = [
                    'loc'        => esc($generatedUrl, 'url'),
                    'lastmod'    => $today,
                    'changefreq' => 'monthly',
                    'priority'   => ($page === 'welcome') ? '1.0' : '0.8',
                ];
            } else {
                log_message('error', 'Sitemap: Failed to generate URL for route - ' . $page);
            }
        }

        // If no URLs were generated, return an error
        if (empty($urls)) {
            $this->response->setStatusCode(500);
            $this->response->setBody('Error: No valid URLs could be generated for the sitemap.');
            return $this->response;
        }

        // Set the correct Content-Type header for an XML sitemap
        $this->response->setHeader('Content-Type', 'application/xml');

        // Pass the array of URLs to the sitemap view and set it as the response body
        $viewContent = view('sitemap/index', ['urls' => $urls]);
        $this->response->setBody($viewContent);

        return $this->response;
    }
}
