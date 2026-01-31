<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('/', ['namespace' => 'App\Modules\Documentation\Controllers'], static function ($routes) {
    // Public Documentation Routes
    $routes->group('', static function ($routes) {
        $routes->get('documentation', 'DocumentationController::index', ['as' => 'documentation']);
        $routes->get('documentation/web', 'DocumentationController::web', ['as' => 'web']);
        $routes->get('documentation/agi', 'DocumentationController::agi', ['as' => 'agi']);
    });
});
