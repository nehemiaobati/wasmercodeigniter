<?php

namespace App\Modules\Gemini\Config;

/**
 * @var \CodeIgniter\Router\RouteCollection $routes
 */

$routes->group('', ['namespace' => 'App\Modules\Gemini\Controllers'], static function ($routes) {

    // Public Routes
    $routes->get('ai-studio', 'GeminiController::publicPage', ['as' => 'gemini.public']);
    $routes->get('gemini', 'GeminiController::index', ['as' => 'gemini.index']);

    // Authenticated Routes
    $routes->group('gemini', ['filter' => 'auth'], static function ($routes) {
        //$routes->get('/', 'GeminiController::index', ['as' => 'gemini.index']);

        // Core Generation
        $routes->post('generate', 'GeminiController::generate', ['as' => 'gemini.generate', 'filter' => ['balance', 'throttle:10,60']]);
        $routes->post('stream', 'GeminiController::stream', ['as' => 'gemini.stream', 'filter' => ['balance', 'throttle:10,60']]);

        // Prompt Management
        $routes->post('prompts/add', 'GeminiController::addPrompt', ['as' => 'gemini.prompts.add']);
        $routes->post('prompts/delete/(:num)', 'GeminiController::deletePrompt/$1', ['as' => 'gemini.prompts.delete']);

        // Memory & Media
        $routes->post('memory/clear', 'GeminiController::clearMemory', ['as' => 'gemini.memory.clear']);
        $routes->post('history', 'GeminiController::fetchHistory', ['as' => 'gemini.history.fetch']);
        $routes->post('history/delete', 'GeminiController::deleteHistory', ['as' => 'gemini.history.delete']);
        $routes->post('upload-media', 'GeminiController::uploadMedia', ['as' => 'gemini.upload_media']);
        $routes->post('delete-media', 'GeminiController::deleteMedia', ['as' => 'gemini.delete_media']);

        // Settings (Unified)
        $routes->post('settings/update', 'GeminiController::updateSetting', ['as' => 'gemini.settings.update']);

        // Downloads & Audio
        $routes->get('serve-audio/(:segment)', 'GeminiController::serveAudio/$1', ['as' => 'gemini.serve_audio']);
        $routes->post('download-document', 'GeminiController::downloadDocument', ['as' => 'gemini.download_document']);

        // Generative Media (Imagen/Veo)
        $routes->post('media/generate', 'MediaController::generate', ['as' => 'gemini.media.generate', 'filter' => ['balance', 'throttle:5,60']]);
        $routes->post('media/poll', 'MediaController::poll', ['as' => 'gemini.media.poll']);
        $routes->post('media/active', 'MediaController::active', ['as' => 'gemini.media.active']);
        $routes->get('media/serve/(:segment)', 'MediaController::serve/$1', ['as' => 'gemini.media.serve']);
    });
});
