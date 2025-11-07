<?php declare(strict_types=1);

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;
use Parsedown;

/**
 * A resilient document generation service.
 * It attempts to use Pandoc first for high-fidelity PDF and Word documents.
 * If Pandoc fails and the request is for a PDF, it falls back to Dompdf.
 */
class DocumentService
{
    protected PandocService $pandocService;

    public function __construct()
    {
        $this->pandocService = service('pandocService');
    }

    /**
     * Generate a document (PDF or Word) from HTML content.
     *
     * @param string $markdownContent The input content in Markdown format.
     * @param string $format The desired output format ('pdf' or 'docx').
     * @return array An array with status, message, and either filePath or fileData.
     */
    public function generate(string $markdownContent, string $format): array
    {
        // 1. Convert Markdown to HTML
        $parsedown = new Parsedown();
        $htmlContent = $parsedown->text($markdownContent);

        // Add basic styling for better output
        $fullHtml = $this->getStyledHtml($htmlContent);

        // 2. Try to generate with Pandoc within a try...catch block
        try {
            if ($this->pandocService->isAvailable()) {
                $pandocResult = $this->pandocService->generate($fullHtml, $format, 'AI-Studio-Output-' . uniqid());
                if ($pandocResult['status'] === 'success') {
                    return $pandocResult;
                }
                // If status is 'error', log it and fall through to the fallback.
                log_message('warning', '[DocumentService] Pandoc failed: ' . ($pandocResult['message'] ?? 'Unknown error') . '. Checking for fallback options.');
            }
        } catch (\Throwable $e) {
            // This will catch any error, including the ErrorException from a disabled shell_exec.
            log_message('error', '[DocumentService] A critical error occurred while trying to use Pandoc: ' . $e->getMessage() . '. Falling back.');
        }


        // 3. Fallback to Dompdf ONLY for PDF format
        if ($format === 'pdf') {
            log_message('info', '[DocumentService] Falling back to Dompdf for PDF generation.');
            return $this->generateWithDompdf($fullHtml);
        }

        // 4. No fallback available for other formats (like docx)
        return [
            'status' => 'error',
            'message' => 'Could not generate the Word document. The primary converter failed and no fallback is available for this format.'
        ];
    }

    /**
     * Generates a PDF using the Dompdf library as a fallback.
     *
     * @param string $htmlContent The fully-styled HTML to convert.
     * @return array An array containing the status and raw PDF data.
     */
    private function generateWithDompdf(string $htmlContent): array
    {
        try {
            set_time_limit(300); // Increase time limit for Dompdf

            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('isFontSubsettingEnabled', false);

            $userId = (int) session()->get('userId');
            $tempDir = WRITEPATH . 'uploads/dompdf_temp/' . $userId;
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0775, true);
            }
            $options->set('tempDir', $tempDir);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($htmlContent, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $output = $dompdf->output();

            return [
                'status' => 'success_fallback',
                'fileData' => $output,
                'message' => 'Document generated using fallback PDF converter.'
            ];

        } catch (\Throwable $e) {
            log_message('error', '[Dompdf Fallback Failed] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'status' => 'error',
                'message' => 'The fallback PDF generator also failed. The error has been logged.'
            ];
        }
    }

    /**
     * Wraps HTML content in a standard document structure with CSS.
     *
     * @param string $htmlContent The core HTML content.
     * @return string The full HTML document.
     */
    private function getStyledHtml(string $htmlContent): string
    {
        return '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <title>AI Response</title>
                <style>
                    body { font-family: "DejaVu Sans", sans-serif; line-height: 1.6; color: #333; font-size: 12px; }
                    h1, h2, h3, h4, h5, h6 { font-family: "DejaVu Sans", sans-serif; margin-bottom: 0.5em; font-weight: bold; }
                    p { margin-bottom: 1em; }
                    ul, ol { margin-bottom: 1em; }
                    strong, b { font-weight: bold; }
                    pre { background-color: #f4f4f4; padding: 10px; border: 1px solid #ddd; border-radius: 4px; white-space: pre-wrap; word-wrap: break-word; font-family: "DejaVu Sans Mono", monospace; }
                    code { font-family: "DejaVu Sans Mono", monospace; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 1em; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>' . $htmlContent . '</body>
            </html>';
    }
}