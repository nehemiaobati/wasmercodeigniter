<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('', ['namespace' => 'App\Modules\Gemini\Controllers'], static function ($routes) {

    // Public Gemini Routes
    $routes->group('', static function ($routes) {
        $routes->get('ai-studio', 'GeminiController::publicPage', ['as' => 'gemini.public']);
    });

    // Authenticated Gemini Routes
    $routes->group('gemini', ['filter' => 'auth'], static function ($routes) {
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
        // [NEW] Route for updating voice output setting
        $routes->post('settings/update-voice-output', 'GeminiController::updateVoiceOutputMode', ['as' => 'gemini.settings.updateVoiceOutputMode']);
        // [NEW] Route for serving TTS audio files
        $routes->get('serve-audio/(:segment)', 'GeminiController::serveAudio/$1', ['as' => 'gemini.serve_audio']);
        // [REVISED] Route for downloading generated content as PDF or Word.
        $routes->post('download-document', 'GeminiController::downloadDocument', ['as' => 'gemini.download_document']);
    });
});
