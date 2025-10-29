<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

//--------------------------------------------------------------------
// Public Routes (No Authentication Required)
//--------------------------------------------------------------------
// These routes are accessible to everyone.
$routes->group('', static function ($routes) {
    // Home & Welcome Page
    $routes->get('/', 'HomeController::landing', ['as' => 'landing']);

    // Documentation Page
    $routes->get('/documentation', 'DocumentationController::index', ['as' => 'documentation']);

    // Sitemap Route for SEO
    $routes->get('sitemap.xml', 'SitemapController::index', ['as' => 'sitemap']);

    // Authentication Routes
    $routes->get('register', 'AuthController::register', ['as' => 'register']);
    $routes->post('register/store', 'AuthController::store', ['as' => 'register.store']);
    $routes->get('verify-email/(:segment)', 'AuthController::verifyEmail/$1', ['as' => 'verify_email']);
    $routes->get('login', 'AuthController::login', ['as' => 'login']);
    $routes->post('login/authenticate', 'AuthController::authenticate', ['as' => 'login.authenticate']);
    $routes->get('logout', 'AuthController::logout', ['as' => 'logout']); // Moved logout here as it's often accessible before full auth

    // Forgot Password Routes
    $routes->get('forgot-password', 'AuthController::forgotPasswordForm', ['as' => 'auth.forgot_password']);
    $routes->post('forgot-password', 'AuthController::sendResetLink', ['as' => 'auth.send_reset_link']);
    $routes->get('reset-password/(:segment)', 'AuthController::resetPasswordForm/$1', ['as' => 'auth.reset_password']);
    $routes->post('reset-password', 'AuthController::updatePassword', ['as' => 'auth.update_password']);

    // Contact Routes
    $routes->get('contact', 'ContactController::form', ['as' => 'contact.form']);
    $routes->post('contact/send', 'ContactController::send', ['as' => 'contact.send']);

    // Portfolio Routes
    $routes->get('portfolio', 'PortfolioController::index', ['as' => 'portfolio.index']);
    $routes->post('portfolio/send', 'PortfolioController::sendEmail', ['as' => 'portfolio.sendEmail']);

    // Legal Routes
    $routes->get('terms', 'HomeController::terms', ['as' => 'terms']);
    $routes->get('privacy', 'HomeController::privacy', ['as' => 'privacy']);

    // Public Service Pages
    $routes->get('ai-studio', 'GeminiController::publicPage', ['as' => 'gemini.public']);
    $routes->get('crypto-query', 'CryptoController::publicPage', ['as' => 'crypto.public']);
});

//--------------------------------------------------------------------
// Authenticated User Routes
//--------------------------------------------------------------------
// These routes require the user to be logged in.
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    // Home for logged-in users
    $routes->get('home', 'HomeController::index', ['as' => 'home']);

    // Account Routes
    $routes->get('account', 'AccountController::index', ['as' => 'account.index']);

    // Admin Panel Routes
    $routes->group('admin', static function ($routes) {
        $routes->get('/', 'AdminController::index', ['as' => 'admin.index']);
        $routes->get('users/(:num)', 'AdminController::show/$1', ['as' => 'admin.users.show']);
        $routes->post('users/update_balance/(:num)', 'AdminController::updateBalance/$1', ['as' => 'admin.users.update_balance']);
        $routes->post('users/delete/(:num)', 'AdminController::delete/$1', ['as' => 'admin.users.delete']);
        $routes->get('users/search', 'AdminController::searchUsers', ['as' => 'admin.users.search']);
        
        // --- Campaign Routes ---
        $routes->get('campaign', 'CampaignController::create', ['as' => 'admin.campaign.create']);
        $routes->post('campaign/send', 'CampaignController::send', ['as' => 'admin.campaign.send']);
        $routes->post('campaign/save', 'CampaignController::save', ['as' => 'admin.campaign.save']);
        $routes->post('campaign/delete/(:num)', 'CampaignController::delete/$1', ['as' => 'admin.campaign.delete']); // ADDED THIS LINE
    });

    // Payment Routes
    $routes->group('payment', static function ($routes) {
        $routes->get('/', 'PaymentsController::index', ['as' => 'payment.index']);
        //$routes->get('initiate', 'Payments::initiate', ['as' => 'payment.initiate']); // Added GET route
        $routes->post('initiate', 'PaymentsController::initiate', ['as' => 'payment.initiate']);
        $routes->get('verify', 'PaymentsController::verify', ['as' => 'payment.verify']);
    });

    // Crypto Routes
    $routes->group('crypto', static function ($routes) {
        $routes->get('/', 'CryptoController::index', ['as' => 'crypto.index']);
        $routes->post('query', 'CryptoController::query', ['as' => 'crypto.query', 'filter' => 'balance']);
    });

    // Gemini API Routes
    $routes->group('gemini', static function ($routes) {
        $routes->get('/', 'GeminiController::index', ['as' => 'gemini.index']);
        $routes->post('generate', 'GeminiController::generate', ['as' => 'gemini.generate', 'filter' => 'balance']);
        $routes->post('prompts/add', 'GeminiController::addPrompt', ['as' => 'gemini.prompts.add']);
        $routes->post('prompts/delete/(:num)', 'GeminiController::deletePrompt/$1', ['as' => 'gemini.prompts.delete']);
        // Route for clearing conversational memory
        $routes->post('memory/clear', 'GeminiController::clearMemory', ['as' => 'gemini.memory.clear']);
        $routes->post('upload-media', 'GeminiController::uploadMedia', ['as' => 'gemini.upload_media']);
        $routes->post('delete-media', 'GeminiController::deleteMedia', ['as' => 'gemini.delete_media']);
        // [NEW] Route for updating assistant mode setting
        $routes->post('settings/update-assistant-mode', 'GeminiController::updateAssistantMode', ['as' => 'gemini.settings.updateAssistantMode']);
    });

});
