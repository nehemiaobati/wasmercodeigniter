<?php

namespace App\Modules\Ollama\Config;

/**
 * @var \CodeIgniter\Router\RouteCollection $routes
 */

$routes->group('ollama', ['namespace' => 'App\Modules\Ollama\Controllers', 'filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'OllamaController::index', ['as' => 'ollama.index']);

    // Core Generation
    $routes->post('generate', 'OllamaController::generate', ['as' => 'ollama.generate']);
    $routes->post('stream', 'OllamaController::stream', ['as' => 'ollama.stream']);

    // File Uploads (reuse Gemini's logic or implement similar)
    $routes->post('upload-media', 'OllamaController::uploadMedia', ['as' => 'ollama.upload_media']);
    $routes->post('delete-media', 'OllamaController::deleteMedia', ['as' => 'ollama.delete_media']);
    $routes->post('download-document', 'OllamaController::downloadDocument', ['as' => 'ollama.download_document']);

    // Settings & Prompts (reuse Gemini's logic or implement similar)
    $routes->post('settings/update', 'OllamaController::updateSetting', ['as' => 'ollama.settings.update']);
    $routes->post('prompts/add', 'OllamaController::addPrompt', ['as' => 'ollama.prompts.add']);
    $routes->post('prompts/delete/(:num)', 'OllamaController::deletePrompt/$1', ['as' => 'ollama.prompts.delete']);
    $routes->post('memory/clear', 'OllamaController::clearMemory', ['as' => 'ollama.memory.clear']);
});
