<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('/', ['namespace' => 'App\Modules\Auth\Controllers'], static function ($routes) {
    // Public Authentication Routes
    $routes->group('', static function ($routes) {
        // Registration
        $routes->get('register', 'AuthController::register', ['as' => 'register']);
        $routes->post('register/store', 'AuthController::store', ['as' => 'register.store', 'filter' => 'throttle:3,60']);

        // Email Verification
        $routes->get('verify-email/(:segment)', 'AuthController::verifyEmail/$1', ['as' => 'verify_email']);

        // Login
        $routes->get('login', 'AuthController::login', ['as' => 'login']);
        $routes->post('login/authenticate', 'AuthController::authenticate', ['as' => 'login.authenticate', 'filter' => 'throttle:5,60']);

        // Logout (accessible before full auth)
        $routes->get('logout', 'AuthController::logout', ['as' => 'logout']);

        // Forgot Password
        $routes->get('forgot-password', 'AuthController::forgotPasswordForm', ['as' => 'auth.forgot_password']);
        $routes->post('forgot-password', 'AuthController::sendResetLink', ['as' => 'auth.send_reset_link', 'filter' => 'throttle:2,60']);

        // Reset Password
        $routes->get('reset-password/(:segment)', 'AuthController::resetPasswordForm/$1', ['as' => 'auth.reset_password']);
        $routes->post('reset-password', 'AuthController::updatePassword', ['as' => 'auth.update_password', 'filter' => 'throttle:2,60']);
    });
});
