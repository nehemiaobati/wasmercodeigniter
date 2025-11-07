<?php declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * CLI-only controller to trigger the machine learning model training process.
 */
class TrainingController extends Controller
{
    /**
     * Runs the training service.
     * This method is only accessible via the command line.
     *
     * @return void
     */
    public function index(): void
    {
        if (!is_cli()) {
            echo "This command can only be run from the command line.";
            return;
        }

        echo "Starting model training process...\n";

        $trainingService = service('trainingService');
        $result = $trainingService->train();

        echo "----------------------------------------\n";
        echo $result['message'] . "\n";
        echo "----------------------------------------\n";
    }
}
