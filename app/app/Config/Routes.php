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


    // Documentation Page
    $routes->get('documentation', '\App\Modules\Documentation\Controllers\DocumentationController::index', ['as' => 'documentation']);
    $routes->get('documentation/web', '\App\Modules\Documentation\Controllers\DocumentationController::web', ['as' => 'web']);
    $routes->get('documentation/agi', '\App\Modules\Documentation\Controllers\DocumentationController::agi', ['as' => 'agi']);


    // Sitemap Route for SEO
    $routes->get('sitemap.xml', 'SitemapController::index', ['as' => 'sitemap']);

    // Authentication Routes
    // Authentication Routes
    $routes->get('register', '\App\Modules\Auth\Controllers\AuthController::register', ['as' => 'register']);
    $routes->post('register/store', '\App\Modules\Auth\Controllers\AuthController::store', ['as' => 'register.store', 'filter' => 'throttle:3,60']);
    $routes->get('verify-email/(:segment)', '\App\Modules\Auth\Controllers\AuthController::verifyEmail/$1', ['as' => 'verify_email']);
    $routes->get('login', '\App\Modules\Auth\Controllers\AuthController::login', ['as' => 'login']);
    $routes->post('login/authenticate', '\App\Modules\Auth\Controllers\AuthController::authenticate', ['as' => 'login.authenticate', 'filter' => 'throttle:5,60']);
    $routes->get('logout', '\App\Modules\Auth\Controllers\AuthController::logout', ['as' => 'logout']); // Moved logout here as it's often accessible before full auth

    // Forgot Password Routes
    $routes->get('forgot-password', '\App\Modules\Auth\Controllers\AuthController::forgotPasswordForm', ['as' => 'auth.forgot_password']);
    $routes->post('forgot-password', '\App\Modules\Auth\Controllers\AuthController::sendResetLink', ['as' => 'auth.send_reset_link', 'filter' => 'throttle:2,60']);
    $routes->get('reset-password/(:segment)', '\App\Modules\Auth\Controllers\AuthController::resetPasswordForm/$1', ['as' => 'auth.reset_password']);
    $routes->post('reset-password', '\App\Modules\Auth\Controllers\AuthController::updatePassword', ['as' => 'auth.update_password', 'filter' => 'throttle:2,60']);

    // Contact Routes
    $routes->get('contact', '\App\Modules\Contact\Controllers\ContactController::form', ['as' => 'contact.form']);
    $routes->post('contact/send', '\App\Modules\Contact\Controllers\ContactController::send', ['as' => 'contact.send', 'filter' => 'throttle:5,60']);

    // Portfolio Routes
    $routes->get('portfolio', '\App\Modules\Portfolio\Controllers\PortfolioController::index', ['as' => 'portfolio.index']);
    $routes->post('portfolio/send', '\App\Modules\Portfolio\Controllers\PortfolioController::sendEmail', ['as' => 'portfolio.sendEmail']);

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

    // Account Routes
    // Account Routes
    $routes->get('account', '\App\Modules\Account\Controllers\AccountController::index', ['as' => 'account.index']);

    // Admin Panel Routes
    $routes->group('admin', ['filter' => 'admin'], static function ($routes) {
        $routes->get('/', '\App\Modules\Admin\Controllers\AdminController::index', ['as' => 'admin.index']);
        $routes->get('users/(:num)', '\App\Modules\Admin\Controllers\AdminController::show/$1', ['as' => 'admin.users.show']);
        $routes->post('users/update_balance/(:num)', '\App\Modules\Admin\Controllers\AdminController::updateBalance/$1', ['as' => 'admin.users.update_balance']);
        $routes->post('users/delete/(:num)', '\App\Modules\Admin\Controllers\AdminController::delete/$1', ['as' => 'admin.users.delete']);
        $routes->get('users/search', '\App\Modules\Admin\Controllers\AdminController::searchUsers', ['as' => 'admin.users.search']);

        // New route for viewing logs
        $routes->get('logs', '\App\Modules\Admin\Controllers\AdminController::logs', ['as' => 'admin.logs']);

        // --- Campaign Routes ---
        $routes->get('campaign', '\App\Modules\Admin\Controllers\CampaignController::create', ['as' => 'admin.campaign.create']);
        $routes->post('campaign/send', '\App\Modules\Admin\Controllers\CampaignController::send', ['as' => 'admin.campaign.send']);
        $routes->post('campaign/save', '\App\Modules\Admin\Controllers\CampaignController::save', ['as' => 'admin.campaign.save']);
        $routes->post('campaign/delete/(:num)', '\App\Modules\Admin\Controllers\CampaignController::delete/$1', ['as' => 'admin.campaign.delete']);
    });
});
