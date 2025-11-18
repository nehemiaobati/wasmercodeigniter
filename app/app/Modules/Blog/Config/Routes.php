<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('/', ['namespace' => 'App\Modules\Blog\Controllers'], static function ($routes) {
    // Public Blog Routes
    $routes->group('', static function ($routes) {
        $routes->get('blog', 'BlogController::index', ['as' => 'blog.index']);
        $routes->get('blog/(:segment)', 'BlogController::show/$1', ['as' => 'blog.show']);
    });

    // Authenticated Admin Blog Routes
    $routes->group('admin', ['filter' => 'auth'], static function ($routes) {
        $routes->get('blog', 'BlogController::adminIndex', ['as' => 'admin.blog.index']);
        $routes->get('blog/new', 'BlogController::create', ['as' => 'admin.blog.create']);
        $routes->post('blog/store', 'BlogController::store', ['as' => 'admin.blog.store']);
        $routes->get('blog/edit/(:num)', 'BlogController::edit/$1', ['as' => 'admin.blog.edit']);
        $routes->post('blog/update/(:num)', 'BlogController::update/$1', ['as' => 'admin.blog.update']);
        $routes->post('blog/delete/(:num)', 'BlogController::delete/$1', ['as' => 'admin.blog.delete']);
    });
});
