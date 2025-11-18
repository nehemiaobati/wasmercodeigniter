<?php declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use App\Modules\Gemini\Libraries\TokenService;
use NlpTools\Documents\TokensDocument;
use NlpTools\Documents\TrainingSet;
use NlpTools\FeatureFactories\DataAsFeatures;
use NlpTools\Classifiers\MultinomialNBClassifier;
use NlpTools\Models\FeatureBasedNB;

/**
 * Service for training the text classification models.
 * This service is intended to be run from a CLI command. It reads a training dataset,
 * processes the text using the TokenService, trains a Naive Bayes classifier,
 * and serializes the trained models to the filesystem for later use.
 */
class TrainingService
{
    protected TokenService $tokenService;

    public function __construct()
    {
        $this->tokenService = service('tokenService');
    }

    /**
     * Executes the full training pipeline.
     *
     * @return array{success: bool, message: string}
     */
    public function train(): array
    {
        // Define paths for training data and model output
        $nlpPath = WRITEPATH . 'nlp/';
        $trainingDataPath = $nlpPath . 'training_data.csv';
        $featureFactoryModelFile = $nlpPath . 'feature_factory.model';
        $classifierModelFile = $nlpPath . 'classifier.model';

        // --- 1. Ensure directories exist ---
        if (!is_dir($nlpPath)) {
            mkdir($nlpPath, 0775, true);
        }
        if (!file_exists($trainingDataPath)) {
            return ['success' => false, 'message' => 'Error: Training data not found at ' . $trainingDataPath];
        }

        // --- 2. Load and Parse Training Data ---
        $trainingData = [];
        if (($handle = fopen($trainingDataPath, 'r')) !== FALSE) {
            fgetcsv($handle); // Skip header row
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) == 2) {
                    $trainingData[] = ['text' => $row[0], 'label' => $row[1]];
                }
            }
            fclose($handle);
        } else {
            return ['success' => false, 'message' => 'Error: Could not open training_data.csv'];
        }

        if (empty($trainingData)) {
            return ['success' => false, 'message' => 'Error: No training data found in training_data.csv'];
        }

        // --- 3. Prepare Training Set using TokenService ---
        $trainingSet = new TrainingSet();
        $labels = [];
        foreach ($trainingData as $item) {
            $processedTokens = $this->tokenService->processText($item['text']);
            $trainingSet->addDocument($item['label'], new TokensDocument($processedTokens));
            $labels[] = $item['label'];
        }
        $classLabels = array_unique($labels);

        // --- 4. Feature Generation and Classifier Training ---
        $featureFactory = new DataAsFeatures();
        $model = new FeatureBasedNB();
        $model->train($featureFactory, $trainingSet);
        $classifier = new MultinomialNBClassifier($featureFactory, $model);
        // --- 5. Serialize and Save Models ---
        if (file_put_contents($featureFactoryModelFile, serialize($featureFactory)) === false) {
            return ['success' => false, 'message' => 'Error: Failed to save feature factory model to ' . $featureFactoryModelFile];
        }
        if (file_put_contents($classifierModelFile, serialize($classifier)) === false) {
            return ['success' => false, 'message' => 'Error: Failed to save classifier model to ' . $classifierModelFile];
        }

        return [
            'success' => true,
            'message' => "Training complete. Models saved successfully to:\n- {$featureFactoryModelFile}\n- {$classifierModelFile}"
        ];
    }
}
