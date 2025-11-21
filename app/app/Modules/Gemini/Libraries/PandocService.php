<?php declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

class PandocService
{
    public function isAvailable(): bool
    {
        return !empty(shell_exec('command -v pandoc 2>/dev/null'));
    }

    public function generate(string $htmlContent, string $outputFormat, string $outputFilename): array
    {
        $userId = session()->get('userId') ?? 0;
        $tempDir = WRITEPATH . 'uploads/pandoc_temp/' . $userId . '/';
        
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);

        // Define Paths
        $ext = ($outputFormat === 'pdf') ? 'pdf' : 'docx';
        $inputFilePath = $tempDir . $outputFilename . '_in.html';
        $outputFilePath = $tempDir . $outputFilename . '.' . $ext;

        // 1. Write Input
        if (file_put_contents($inputFilePath, $htmlContent) === false) {
            return ['status' => 'error', 'message' => 'Write permission denied.'];
        }

        // 2. Execute Command
        $cmd = sprintf(
            'pandoc --standalone %s -o %s',
            escapeshellarg($inputFilePath),
            escapeshellarg($outputFilePath)
        );

        // Using 2>&1 to capture stderr in case of issues
        exec($cmd . ' 2>&1', $output, $returnVar);

        // 3. Cleanup Input immediately
        if (file_exists($inputFilePath)) @unlink($inputFilePath);

        // 4. Validate Output
        if ($returnVar !== 0 || !file_exists($outputFilePath)) {
            log_message('error', '[PandocService] Failed: ' . implode("\n", $output));
            // Try to cleanup output if it was partially created (0 bytes)
            if (file_exists($outputFilePath)) @unlink($outputFilePath);
            
            return ['status' => 'error', 'message' => 'Conversion process failed.'];
        }

        // Success - Return path. Parent DocumentService will read & delete it.
        return [
            'status' => 'success',
            'filePath' => $outputFilePath
        ];
    }
}