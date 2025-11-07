<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class Train extends BaseCommand
{
    /**
     * The Command's Group.
     *
     * @var string
     */
    protected $group = 'App';

    /**
     * The Command's Name.
     *
     * @var string
     */
    protected $name = 'train';

    /**
     * The Command's Description.
     *
     * @var string
     */
    protected $description = 'Runs the AI text classification training service to generate models.';

    /**
     * The Command's Usage.
     *
     * @var string
     */
    protected $usage = 'train';

    /**
     * The Command's Arguments.
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options.
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
        CLI::write('Starting model training process...', 'yellow');

        $trainingService = service('trainingService');
        $result = $trainingService->train();

        CLI::write("----------------------------------------", 'light_gray');
        if ($result['success']) {
            CLI::write($result['message'], 'green');
        } else {
            CLI::error($result['message']);
        }
        CLI::write("----------------------------------------", 'light_gray');
    }
}
