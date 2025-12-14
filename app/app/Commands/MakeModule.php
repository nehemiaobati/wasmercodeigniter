<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MakeModule extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Generators';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'make:module';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Generates a new Module with the standard MVC-S structure.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'make:module [name]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'name' => 'The name of the module to create (PascalCase).',
    ];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $moduleName = array_shift($params);

        if (empty($moduleName)) {
            $moduleName = CLI::prompt('Module Name (PascalCase)', null, 'required');
        }

        $moduleName = ucfirst($moduleName);
        $modulePath = APPPATH . 'Modules/' . $moduleName;

        if (is_dir($modulePath)) {
            CLI::error("Module '$moduleName' already exists.");
            return;
        }

        CLI::write("Creating module: $moduleName", 'yellow');

        // 1. Create Directory Structure
        $directories = [
            'Config',
            'Controllers',
            'Database/Migrations',
            'Database/Seeds',
            'Entities',
            'Libraries',
            'Models',
            'Views',
        ];

        foreach ($directories as $dir) {
            $path = $modulePath . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
                CLI::write("  Created: $dir", 'green');
            }
        }

        // 2. Generate Files
        $this->_createRoutesFile($modulePath, $moduleName);
        $this->_createControllerFile($modulePath, $moduleName);
        $this->_createEntityFile($modulePath, $moduleName);
        $this->_createServiceFile($modulePath, $moduleName);
        $this->_createModelFile($modulePath, $moduleName);
        $this->_createViewFile($modulePath, $moduleName);

        // 3. Update Autoload.php
        $this->_updateAutoload($moduleName);

        CLI::write("Module '$moduleName' created successfully!", 'green');
        CLI::write("Don't forget to run 'php spark optimize' if you are in production.", 'yellow');
    }

    private function _createRoutesFile($path, $name)
    {
        $lowerName = strtolower($name);
        $content = <<<PHP
<?php

namespace App\Modules\\$name\Config;

/**
 * @var \CodeIgniter\Router\RouteCollection \$routes
 */

\$routes->group('$lowerName', ['namespace' => 'App\Modules\\$name\Controllers'], static function (\$routes) {
    \$routes->get('/', '{$name}Controller::index', ['as' => '$lowerName.index']);
});
PHP;
        file_put_contents($path . '/Config/Routes.php', $content);
    }

    private function _createControllerFile($path, $name)
    {
        $content = <<<PHP
<?php

namespace App\Modules\\$name\Controllers;

use App\Controllers\BaseController;

class {$name}Controller extends BaseController
{
    public function index()
    {
        return view('App\Modules\\$name\Views\index', [
            'pageTitle' => '$name Module',
        ]);
    }
}
PHP;
        file_put_contents($path . '/Controllers/' . $name . 'Controller.php', $content);
    }

    private function _createEntityFile($path, $name)
    {
        $content = <<<PHP
<?php

namespace App\Modules\\$name\Entities;

use CodeIgniter\Entity\Entity;

class $name extends Entity
{
    protected \$datamap = [];
    protected \$dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected \$casts   = [];
}
PHP;
        file_put_contents($path . '/Entities/' . $name . '.php', $content);
    }

    private function _createServiceFile($path, $name)
    {
        $content = <<<PHP
<?php

namespace App\Modules\\$name\Libraries;

class {$name}Service
{
    public function __construct()
    {
        // Initialize dependencies
    }
}
PHP;
        file_put_contents($path . '/Libraries/' . $name . 'Service.php', $content);
    }

    private function _createModelFile($path, $name)
    {
        $lowerName = strtolower($name);
        $content = <<<PHP
<?php

namespace App\Modules\\$name\Models;

use CodeIgniter\Model;
use App\Modules\\$name\Entities\\$name;

class {$name}Model extends Model
{
    protected \$table            = '{$lowerName}_table'; // TODO: Update table name
    protected \$primaryKey       = 'id';
    protected \$useAutoIncrement = true;
    protected \$returnType       = $name::class;
    protected \$useSoftDeletes   = false;
    protected \$protectFields    = true;
    protected \$allowedFields    = [];

    // Dates
    protected \$useTimestamps = true;
    protected \$dateFormat    = 'datetime';
    protected \$createdField  = 'created_at';
    protected \$updatedField  = 'updated_at';
    protected \$deletedField  = 'deleted_at';
}
PHP;
        file_put_contents($path . '/Models/' . $name . 'Model.php', $content);
    }

    private function _createViewFile($path, $name)
    {
        $content = <<<PHP
<?= \$this->extend('layouts/default') ?>

<?= \$this->section('content') ?>
<div class="container my-5">
    <div class="blueprint-header mb-4">
        <h1>$name Module</h1>
        <p class="text-muted">Generated by make:module</p>
    </div>

    <div class="card blueprint-card">
        <div class="card-body">
            <p>Welcome to your new module!</p>
        </div>
    </div>
</div>
<?= \$this->endSection() ?>
PHP;
        file_put_contents($path . '/Views/index.php', $content);
    }

    private function _updateAutoload($name)
    {
        $autoloadPath = APPPATH . 'Config/Autoload.php';
        $content = file_get_contents($autoloadPath);

        $newLine = "    'App\\\\Modules\\\\$name' => APPPATH . 'Modules/$name',";

        // Check if already added
        if (strpos($content, $newLine) !== false) {
            return;
        }

        // Find the psr4 array and append
        $pattern = '/(\$psr4\s*=\s*\[)(.*?)(\];)/s';

        if (preg_match($pattern, $content)) {
            // Insert before the closing bracket of psr4 array
            $replacement = "$1$2$newLine\n        $3";
            $newContent = preg_replace($pattern, $replacement, $content);

            if ($newContent) {
                file_put_contents($autoloadPath, $newContent);
                CLI::write("  Updated: app/Config/Autoload.php", 'green');
            } else {
                CLI::error("Failed to update Autoload.php automatically. Please add the namespace manually.");
            }
        } else {
            CLI::error("Could not find \$psr4 array in Autoload.php. Please add the namespace manually.");
        }
    }
}
