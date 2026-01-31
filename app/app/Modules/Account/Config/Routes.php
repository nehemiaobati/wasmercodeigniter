<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('/', ['namespace' => 'App\Modules\Account\Controllers'], static function ($routes) {
    // Authenticated User Account Routes
    $routes->group('', ['filter' => 'auth'], static function ($routes) {
        $routes->get('account', 'AccountController::index', ['as' => 'account.index']);
    });
});
