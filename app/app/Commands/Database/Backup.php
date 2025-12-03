<?php

namespace App\Commands\Database;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class Backup extends BaseCommand
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
    protected $name = 'db:backup';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Backups the database using mysqldump.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'db:backup [filename]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'filename' => 'The filename of the backup file. Defaults to backup-YYYY-MM-DD_HH-MM-SS.sql',
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

        if (empty($filename)) {
            $filename = 'backup-' . date('Y-m-d_H-i-s') . '.sql';
        }

        if (! str_ends_with($filename, '.sql')) {
            $filename .= '.sql';
        }

        $path = WRITEPATH . 'backups/' . $filename;

        $db = Database::connect();
        $hostname = $db->hostname;
        $username = $db->username;
        $password = $db->password;
        $database = $db->database;
        $port     = $db->port;

        // Build the command
        // Note: This assumes mysqldump is in the system path.
        // If password is empty, don't include -p
        $passwordPart = empty($password) ? '' : "-p'{$password}'";

        // Handle port if it's not default
        $portPart = ($port && $port !== 3306) ? "--port={$port}" : '';

        // Add flags for consistency and portability
        // --single-transaction: Ensures consistent backup without locking tables (InnoDB)
        // --set-gtid-purged=OFF: Removes GTID info for easier restoring on different servers
        $command = "mysqldump --single-transaction --set-gtid-purged=OFF -h {$hostname} -u {$username} {$passwordPart} {$portPart} {$database} > {$path}";

        CLI::write("Backing up database '{$database}' to '{$path}'...", 'yellow');

        // Execute the command
        $output = [];
        $returnVar = null;
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            CLI::write('Database backup created successfully.', 'green');
        } else {
            CLI::error('Failed to create database backup.');
            CLI::error('Command executed: ' . $command); // Be careful showing password in logs/output
            if (! empty($output)) {
                CLI::newLine();
                foreach ($output as $line) {
                    CLI::write($line);
                }
            }
        }
    }
}
