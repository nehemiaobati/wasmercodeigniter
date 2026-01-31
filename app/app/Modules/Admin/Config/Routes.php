<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('/', ['namespace' => 'App\Modules\Admin\Controllers'], static function ($routes) {
    // Admin Panel Routes (requires auth + admin filter)
    $routes->group('admin', ['filter' => ['auth', 'admin']], static function ($routes) {
        // Dashboard
        $routes->get('/', 'AdminController::index', ['as' => 'admin.index']);

        // User Management
        $routes->get('users/(:num)', 'AdminController::show/$1', ['as' => 'admin.users.show']);
        $routes->post('users/update_balance/(:num)', 'AdminController::updateBalance/$1', ['as' => 'admin.users.update_balance']);
        $routes->post('users/delete/(:num)', 'AdminController::delete/$1', ['as' => 'admin.users.delete']);
        $routes->get('users/search', 'AdminController::searchUsers', ['as' => 'admin.users.search']);

        // Logs
        $routes->get('logs', 'AdminController::logs', ['as' => 'admin.logs']);

        // --- Campaign Routes ---
        $routes->get('campaign', 'CampaignController::create', ['as' => 'admin.campaign.create']);
        $routes->post('campaign/send', 'CampaignController::send', ['as' => 'admin.campaign.send']);
        $routes->post('campaign/save', 'CampaignController::save', ['as' => 'admin.campaign.save']);
        $routes->post('campaign/delete/(:num)', 'CampaignController::delete/$1', ['as' => 'admin.campaign.delete']);
        $routes->get('campaign/monitor/(:num)', 'CampaignController::monitor/$1', ['as' => 'admin.campaign.monitor']);
        $routes->get('campaign/process-batch/(:num)', 'CampaignController::process_batch/$1', ['as' => 'admin.campaign.process_batch']);
        $routes->get('campaign/retry/(:num)', 'CampaignController::start_retry/$1', ['as' => 'admin.campaign.start_retry']);
        $routes->get('campaign/pause/(:num)', 'CampaignController::pause/$1', ['as' => 'admin.campaign.pause']);
        $routes->get('campaign/resume/(:num)', 'CampaignController::resume/$1', ['as' => 'admin.campaign.resume']);
    });
});
