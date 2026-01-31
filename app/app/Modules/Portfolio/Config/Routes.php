<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('/', ['namespace' => 'App\Modules\Portfolio\Controllers'], static function ($routes) {
    // Public Portfolio Routes
    $routes->group('', static function ($routes) {
        $routes->get('portfolio', 'PortfolioController::index', ['as' => 'portfolio.index']);
        $routes->post('portfolio/send', 'PortfolioController::sendEmail', ['as' => 'portfolio.sendEmail']);
    });
});
