<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('/', ['namespace' => 'App\Modules\Payments\Controllers'], static function ($routes) {
    // Routes for the Payments module
    $routes->group('payment', static function ($routes) {
        $routes->get('/', 'PaymentsController::index', ['as' => 'payment.index']);
        // Note: The commented-out GET route for 'initiate' is not included as per the original file.
        $routes->post('initiate', 'PaymentsController::initiate', ['as' => 'payment.initiate']);
        $routes->get('verify', 'PaymentsController::verify', ['as' => 'payment.verify']);
    });
});
