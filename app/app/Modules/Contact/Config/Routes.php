<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('/', ['namespace' => 'App\Modules\Contact\Controllers'], static function ($routes) {
    // Public Contact Routes
    $routes->group('', static function ($routes) {
        $routes->get('contact', 'ContactController::form', ['as' => 'contact.form']);
        $routes->post('contact/send', 'ContactController::send', ['as' => 'contact.send', 'filter' => 'throttle:5,60']);
    });
});
