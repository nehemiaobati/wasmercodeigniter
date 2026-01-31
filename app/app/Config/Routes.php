<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

//--------------------------------------------------------------------
// CLI Routes
//--------------------------------------------------------------------
// These routes are only accessible from the command line.
$routes->cli('train', 'TrainingController::index', ['as' => 'train.run']);

//--------------------------------------------------------------------
// Public Routes (No Authentication Required)
//--------------------------------------------------------------------
// These routes are accessible to everyone.
$routes->group('', static function ($routes) {
    // Home & Welcome Page
    $routes->get('/', 'HomeController::landing', ['as' => 'landing']);

    // Sitemap Route for SEO
    $routes->get('sitemap.xml', 'SitemapController::index', ['as' => 'sitemap']);

    // Legal Routes
    $routes->get('terms', 'HomeController::terms', ['as' => 'terms']);
    $routes->get('privacy', 'HomeController::privacy', ['as' => 'privacy']);

    // Public Service Pages
    $routes->post('cookie/accept', 'HomeController::acceptCookie', ['as' => 'cookie.accept']);
});

//--------------------------------------------------------------------
// Authenticated User Routes
//--------------------------------------------------------------------
// These routes require the user to be logged in.
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    // Home for logged-in users
    $routes->get('home', 'HomeController::index', ['as' => 'home']);
});
