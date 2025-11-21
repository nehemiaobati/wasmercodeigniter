<?php declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;
use Parsedown;

class DocumentService
{
    protected PandocService $pandocService;

    public function __construct()
    {
        $this->pandocService = service('pandocService');
    }

    /**
     * Unified generation method. 
     * Always returns 'fileData' (binary string) and handles intermediate file cleanup.
     */
    public function generate(string $markdownContent, string $format): array
    {
        // 1. Prepare HTML
        $parsedown = new Parsedown();
        $parsedown->setBreaksEnabled(true);
        $htmlContent = $parsedown->text($markdownContent);
        $fullHtml = $this->getStyledHtml($htmlContent);

        // 2. Strategy A: Pandoc (Preferred)
        // Checks availability inside the service to keep controller clean
        if ($this->pandocService->isAvailable()) {
            $pandocResult = $this->pandocService->generate($fullHtml, $format, 'temp_' . uniqid());
            
            if ($pandocResult['status'] === 'success' && file_exists($pandocResult['filePath'])) {
                // READ -> DELETE -> RETURN
                // This ensures no files are left in the path, making it behave like Dompdf (memory only)
                $fileData = file_get_contents($pandocResult['filePath']);
                @unlink($pandocResult['filePath']); 
                
                return [
                    'status' => 'success', 
                    'fileData' => $fileData
                ];
            }
            
            // Log warning if Pandoc failed, but continue to fallback
            log_message('warning', '[DocumentService] Pandoc failed: ' . ($pandocResult['message'] ?? 'Unknown') . '. Attempting fallback.');
        }

        // 3. Strategy B: Dompdf (Fallback for PDF only)
        if ($format === 'pdf') {
            return $this->generateWithDompdf($fullHtml);
        }

        // 4. Failure
        return [
            'status' => 'error',
            'message' => 'Could not generate document. Primary converter failed and no fallback available.'
        ];
    }

    private function generateWithDompdf(string $htmlContent): array
    {
        try {
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', true);
            //For wasmer production environments always include this
            $options->set('isFontSubsettingEnabled', false);


            // User-specific temp dir for Dompdf internal processing
            $userId = session()->get('userId') ?? 0;
            $tempDir = WRITEPATH . 'uploads/dompdf_temp/' . $userId;
            if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
            $options->set('tempDir', $tempDir);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($htmlContent, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return [
                'status' => 'success',
                'fileData' => $dompdf->output() // Returns string directly
            ];
        } catch (\Throwable $e) {
            log_message('error', '[DocumentService] Dompdf error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Fallback generation failed.'];
        }
    }

    private function getStyledHtml(string $htmlContent): string
    {
        return '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8"/>
                <style>
                    body { font-family: "DejaVu Sans", sans-serif; line-height: 1.5; font-size: 12px; color: #111; }
                    h1, h2, h3 { color: #000; margin-top: 1.5em; margin-bottom: 0.5em; }
                    pre { background: #f0f0f0; padding: 10px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; }
                    table { width: 100%; border-collapse: collapse; margin: 1em 0; }
                    th, td { border: 1px solid #ddd; padding: 6px; }
                    blockquote { border-left: 3px solid #ccc; margin: 0; padding-left: 10px; color: #555; }
                </style>
            </head>
            <body>' . $htmlContent . '</body>
            </html>';
    }
}