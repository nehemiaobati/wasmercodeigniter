<?php declare(strict_types=1);

namespace App\Libraries;

use Exception;

/**
 * Service wrapper for the Pandoc command-line tool.
 * Handles document conversion from HTML to various formats like PDF and DOCX.
 */
class PandocService
{
    /**
     * Checks if the pandoc command is available on the system.
     *
     * @return bool True if pandoc is executable, false otherwise.
     */
    public function isAvailable(): bool
    {
        // shell_exec('command -v pandoc') returns the path to pandoc if it exists, or an empty string.
        // Adding 2>/dev/null to redirect stderr to null, suppressing "command not found" errors.
        $result = shell_exec('command -v pandoc 2>/dev/null');
        return !empty($result);
    }

    /**
     * Generates a document from HTML content using Pandoc.
     *
     * @param string $htmlContent The HTML input.
     * @param string $outputFormat The desired output format ('pdf' or 'docx').
     * @param string $outputFilename The desired name for the output file (without extension).
     * @return array An array containing the status, message, and file path on success, or an error message on failure.
     */
    public function generate(string $htmlContent, string $outputFormat, string $outputFilename): array
    {
        if (!$this->isAvailable()) {
            return ['status' => 'error', 'message' => 'Pandoc command not found on the server.'];
        }

        $userId = (int) session()->get('userId');
        $tempDir = WRITEPATH . 'uploads/pandoc_temp/' . $userId . '/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $extension = ($outputFormat === 'pdf') ? 'pdf' : 'docx';
        $inputFilePath = tempnam($tempDir, 'pandoc_in_') . '.html';
        $outputFilePath = $tempDir . $outputFilename . '.' . $extension;

        if (file_put_contents($inputFilePath, $htmlContent) === false) {
            return ['status' => 'error', 'message' => 'Failed to create temporary HTML input file.'];
        }

        // Build the pandoc command
        // --standalone: Create a self-contained document
        // --from html: Specify input format
        // --to ...: Specify output format
        // --output: Specify output file path
        // --pdf-engine=xelatex: A common engine that handles UTF-8 well. Requires LaTeX installed on the server.
        $command = sprintf(
            'pandoc --standalone %s --output %s',
            escapeshellarg($inputFilePath),
            escapeshellarg($outputFilePath)
        );


        $output = null;
        $return_var = null;
        exec($command . ' 2>&1', $output, $return_var);

        // Clean up the input file immediately
        if (file_exists($inputFilePath)) {
            unlink($inputFilePath);
        }

        if ($return_var !== 0) {
            $error_message = "Pandoc execution failed. Error code: {$return_var}. Output: " . implode("\n", $output);
            log_message('error', '[PandocService] ' . $error_message);
            // Clean up failed output file
            if (file_exists($outputFilePath)) {
                unlink($outputFilePath);
            }
            return ['status' => 'error', 'message' => 'Document generation failed. This may be due to complex content or a server configuration issue. The error has been logged.'];
        }

        if (!file_exists($outputFilePath)) {
            return ['status' => 'error', 'message' => 'Pandoc executed but the output file was not created.'];
        }

        return [
            'status' => 'success',
            'message' => 'Document generated successfully.',
            'filePath' => $outputFilePath
        ];
    }
}