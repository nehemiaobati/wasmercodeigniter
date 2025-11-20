<?php

namespace App\Modules\Gemini\Config;

/**
 * @var \CodeIgniter\Router\RouteCollection $routes
 */

$routes->group('', ['namespace' => 'App\Modules\Gemini\Controllers'], static function ($routes) {

    // Public Routes
    $routes->get('ai-studio', 'GeminiController::publicPage', ['as' => 'gemini.public']);

    // Authenticated Routes
    $routes->group('gemini', ['filter' => 'auth'], static function ($routes) {
        $routes->get('/', 'GeminiController::index', ['as' => 'gemini.index']);
        
        // Core Generation
        $routes->post('generate', 'GeminiController::generate', ['as' => 'gemini.generate', 'filter' => 'balance']);
        
        // Prompt Management
        $routes->post('prompts/add', 'GeminiController::addPrompt', ['as' => 'gemini.prompts.add']);
        $routes->post('prompts/delete/(:num)', 'GeminiController::deletePrompt/$1', ['as' => 'gemini.prompts.delete']);
        
        // Memory & Media
        $routes->post('memory/clear', 'GeminiController::clearMemory', ['as' => 'gemini.memory.clear']);
        $routes->post('upload-media', 'GeminiController::uploadMedia', ['as' => 'gemini.upload_media']);
        $routes->post('delete-media', 'GeminiController::deleteMedia', ['as' => 'gemini.delete_media']);
        
        // Settings (Unified)
        $routes->post('settings/update', 'GeminiController::updateSetting', ['as' => 'gemini.settings.update']);
        
        // Downloads & Audio
        $routes->get('serve-audio/(:segment)', 'GeminiController::serveAudio/$1', ['as' => 'gemini.serve_audio']);
        $routes->post('download-document', 'GeminiController::downloadDocument', ['as' => 'gemini.download_document']);
    });
});