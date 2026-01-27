<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('/', ['namespace' => 'App\Modules\Affiliate\Controllers'], static function ($routes) {
    // Public Affiliate Routes
    $routes->group('', static function ($routes) {
        // Amazon Affiliate Redirect Route
        $routes->get('amazon/(:segment)', 'AffiliateController::redirect/$1', ['as' => 'amazon.redirect']);
    });

    // Authenticated Admin Affiliate Routes
    $routes->group('admin', ['filter' => ['auth', 'admin']], static function ($routes) {
        // Affiliate Link Management
        $routes->get('affiliate-links', 'AffiliateController::index', ['as' => 'admin.affiliate.index']);
        $routes->get('affiliate-links/new', 'AffiliateController::create', ['as' => 'admin.affiliate.create']);
        $routes->post('affiliate-links/store', 'AffiliateController::store', ['as' => 'admin.affiliate.store']);
        $routes->get('affiliate-links/edit/(:num)', 'AffiliateController::edit/$1', ['as' => 'admin.affiliate.edit']);
        $routes->post('affiliate-links/update/(:num)', 'AffiliateController::update/$1', ['as' => 'admin.affiliate.update']);
        $routes->post('affiliate-links/delete/(:num)', 'AffiliateController::delete/$1', ['as' => 'admin.affiliate.delete']);
    });
});
