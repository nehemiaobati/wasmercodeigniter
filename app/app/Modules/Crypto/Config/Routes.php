<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('/', ['namespace' => 'App\Modules\Crypto\Controllers'], static function ($routes) {
    // Public Routes for Crypto Module
    $routes->group('', static function ($routes) {
        $routes->get('crypto-query', 'CryptoController::publicPage', ['as' => 'crypto.public']);
    });

    // Authenticated User Routes for Crypto Module
    $routes->group('crypto', ['filter' => 'auth'], static function ($routes) {
        $routes->get('/', 'CryptoController::index', ['as' => 'crypto.index']);
        $routes->post('query', 'CryptoController::query', ['as' => 'crypto.query', 'filter' => 'balance']);
    });
});
