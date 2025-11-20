<?php

use CodeIgniter\Router\RouteCollection;
use App\Modules\Ollama\Controllers\OllamaController;

/**
 * @var RouteCollection $routes
 */

$routes->group('ollama', ['namespace' => 'App\Modules\Ollama\Controllers', 'filter' => 'auth'], static function ($routes) {
    $routes->get('/', [OllamaController::class, 'index'], ['as' => 'ollama.index']);
    $routes->post('chat', [OllamaController::class, 'chat'], ['as' => 'ollama.chat']);
    $routes->post('clear', [OllamaController::class, 'clearHistory'], ['as' => 'ollama.clear']);
});