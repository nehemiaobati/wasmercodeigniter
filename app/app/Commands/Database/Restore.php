<?php

namespace App\Commands\Database;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class Restore extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Database';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'db:restore';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Restores the database from a backup file.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'db:restore [filename]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'filename' => 'The filename of the backup file to restore.',
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $filename = array_shift($params);
        $backupDir = WRITEPATH . 'backups/';

        // If no filename provided, list available backups
        if (empty($filename)) {
            $files = glob($backupDir . '*.sql');

            if (empty($files)) {
                CLI::error('No backup files found in ' . $backupDir);
                return;
            }

            // Sort by modified time, newest first
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $options = [];
            foreach ($files as $index => $file) {
                // Use numeric index for easier selection
                $options[$index] = basename($file) . ' (' . date('Y-m-d H:i:s', filemtime($file)) . ')';
            }

            $selectedKey = CLI::promptByKey('Select a backup to restore:', $options);
            $filename = basename($files[$selectedKey]);
        }

        $path = $backupDir . $filename;

        if (! file_exists($path)) {
            // Try adding .sql extension
            if (file_exists($path . '.sql')) {
                $path .= '.sql';
            } else {
                CLI::error("Backup file not found: {$path}");
                return;
            }
        }

        CLI::write("Restoring database from '{$path}'...", 'yellow');

        $db = Database::connect();
        $hostname = $db->hostname;
        $username = $db->username;
        $password = $db->password;
        $database = $db->database;
        $port     = $db->port;

        // Build the command
        // Note: This assumes mysql client is in the system path.
        $passwordPart = empty($password) ? '' : "-p'{$password}'";
        $portPart = ($port && $port !== 3306) ? "--port={$port}" : '';

        // Use -f (force) to continue even if errors occur (optional, but often helpful for restores)
        // But for safety, let's stick to standard restore first.
        $command = "mysql -h {$hostname} -u {$username} {$passwordPart} {$portPart} {$database} < {$path}";

        // Execute the command
        $output = [];
        $returnVar = null;
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            CLI::write('Database restored successfully.', 'green');
        } else {
            CLI::error('Failed to restore database.');
            CLI::error('Command executed: ' . $command);
            if (! empty($output)) {
                CLI::newLine();
                foreach ($output as $line) {
                    CLI::write($line);
                }
            }
        }
    }
}
