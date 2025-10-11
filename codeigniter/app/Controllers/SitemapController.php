<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class SitemapController extends BaseController
{
    /**
     * Generates the sitemap.xml file.
     */
    public function index(): ResponseInterface
    {
        // Array of public-facing, static pages
        $pages = [
            'welcome',
            'register',
            'login',
            'contact.form',
            'portfolio.index',
            'terms',
            'privacy',
        ];

        $xmlContent = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
        $xmlContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $urlsAdded = false; // Flag to check if any URLs were actually added

        foreach ($pages as $page) {
            $generatedUrl = url_to($page); // Get the URL

            if ($generatedUrl) {
                $urlsAdded = true; // Mark that at least one URL was added
                $xmlContent .= '    <url>' . "\n";
                // Use default esc() instead of esc($url, 'xml')
                $xmlContent .= '        <loc>' . esc($generatedUrl) . '</loc>' . "\n";
                $xmlContent .= '        <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
                $xmlContent .= '        <changefreq>monthly</changefreq>' . "\n";
                $xmlContent .= '        <priority>' . (($page === 'welcome') ? '1.0' : '0.8') . '</priority>' . "\n";
                $xmlContent .= '    </url>' . "\n";
            } else {
                log_message('error', 'Sitemap: Failed to generate URL for route - ' . $page);
            }
        }

        $xmlContent .= '</urlset>';

        // If no valid URLs were generated, return an error response
        if (!$urlsAdded) {
            $response = service('response');
            $response->setBody('Error generating sitemap: No valid URLs could be generated for the defined routes.');
            $response->setStatusCode(500); // Internal Server Error
            $response->setHeader('Content-Type', 'text/plain');
            return $response;
        }

        // Create a new response object
        $response = service('response');

        // Set the response body and content type
        $response->setBody($xmlContent);
        $response->setHeader('Content-Type', 'application/xml');

        return $response;
    }
}
