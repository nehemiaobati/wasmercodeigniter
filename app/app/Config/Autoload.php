<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

class Autoload extends AutoloadConfig
{
    /**
     * Registers an array of namespaces for the PSR-4 autoloader.
     *
     * @var array<string, string>
     */
    public $psr4 = [
        APP_NAMESPACE => APPPATH,
        //\'App\Modules' => APPPATH . 'Modules', // This is the required addition
        'App\Modules\Payments' => APPPATH . 'Modules/Payments', // Added for the Payments module
        'App\Modules\Gemini' => APPPATH . 'Modules/Gemini', // Added for the Gemini module
        'App\Modules\Crypto' => APPPATH . 'Modules/Crypto', // Added for the Crypto module
        'App\Modules\Blog' => APPPATH . 'Modules/Blog',
        'App\Modules\Ollama' => APPPATH . 'Modules/Ollama',
    ];

    /**
     * Registers an array of files that will be included on each request.
     *
     * @var array<string, string>
     */
    public $files = [];

    /**
     * Registers a mapping of class names to file paths.
     *
     * @var array<string, string>
     */
    public $classmap = [];

    /**
     * Registers a mapping of helper file names to their file paths.
     *
     * @var array<string, string>
     */
    public $helpers = [];
}
