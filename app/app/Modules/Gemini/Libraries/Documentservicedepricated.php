<?php

declare(strict_types=1);

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
     * 
     * @param string $markdownContent The markdown content to convert
     * @param string $format Output format: 'pdf' or 'docx'
     * @param array $metadata Optional document metadata (title, author, subject, keywords, etc.)
     * @return array ['status' => 'success'|'error', 'fileData' => string|null, 'message' => string|null]
     */
    public function generate(string $markdownContent, string $format, array $metadata = []): array
    {
        // 1. Prepare metadata with defaults
        $defaults = [
            'title' => 'AI Studio Document',
            'author' => 'AI Content Studio',
            'subject' => 'Generated Content',
            'keywords' => 'AI, Content, Report',
            'creator' => 'AI Content Studio - Powered by Gemini',
        ];
        $meta = array_merge($defaults, $metadata);

        // 2. Prepare HTML
        $parsedown = new Parsedown();
        $parsedown->setBreaksEnabled(true);
        $htmlContent = $parsedown->text($markdownContent);
        $styledHtml = $this->_getStyledHtml($htmlContent, $metadata['title'] ?? 'Document');

        // 3. Strategy A: Pandoc (Preferred)
        // Checks availability inside the service to keep controller clean
        if ($this->pandocService->isAvailable()) {
            $pandocResult = $this->pandocService->generate($styledHtml, $format, 'temp_' . bin2hex(random_bytes(8)));

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

        // 4. Strategy B: Fallbacks
        if ($format === 'pdf') {
            // Dompdf fallback for PDF
            return $this->_generateWithDompdf($htmlContent, $meta);
        } elseif ($format === 'docx') {
            // PHPWord fallback for DOCX
            // Pass raw markdown because we need to pre-process it specifically for PHPWord
            return $this->_generateWithPHPWord($markdownContent, $meta);
        }

        // 5. Failure
        return [
            'status' => 'error',
            'message' => 'Could not generate document. Unsupported format or all converters failed.'
        ];
    }

    /**
     * Generates a PDF using Dompdf.
     *
     * @param string $htmlContent The HTML content to render.
     * @param array $metadata Document metadata.
     * @return array Result array with status and fileData or message.
     */
    private function _generateWithDompdf(string $htmlContent, array $metadata): array
    {
        try {
            $options = new Options();
            $options->set('defaultFont', 'Calibri');
            $options->set('isRemoteEnabled', true);
            // CRITICAL: For wasmer production environments always include this
            $options->set('isFontSubsettingEnabled', false);
            $options->set('defaultPaperSize', 'letter');
            $options->set('defaultPaperOrientation', 'portrait');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', false); // Security

            // User-specific temp dir for Dompdf internal processing
            $userId = session()->get('userId') ?? 0;
            $tempDir = WRITEPATH . 'uploads/dompdf_temp/' . $userId;
            if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
            $options->set('tempDir', $tempDir);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($htmlContent, 'UTF-8');
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->render();

            // Set document metadata using Dompdf 2.0 API
            $dompdf->addInfo('Title', $metadata['title']);
            $dompdf->addInfo('Author', $metadata['author']);
            $dompdf->addInfo('Subject', $metadata['subject']);
            $dompdf->addInfo('Keywords', $metadata['keywords']);
            $dompdf->addInfo('Creator', $metadata['creator']);

            return [
                'status' => 'success',
                'fileData' => $dompdf->output()
            ];
        } catch (\Throwable $e) {
            log_message('error', '[DocumentService] Dompdf error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'PDF generation failed.'];
        }
    }

    private function _generateWithPHPWord(string $markdownContent, array $metadata): array
    {
        try {
            // Convert Markdown to HTML locally for PHPWord
            $parsedown = new Parsedown();
            $parsedown->setBreaksEnabled(true);
            $htmlContent = $parsedown->text($markdownContent);

            $phpWord = new \PhpOffice\PhpWord\PhpWord();

            // Set document properties
            $properties = $phpWord->getDocInfo();
            $properties->setCreator($metadata['creator']);
            $properties->setTitle($metadata['title']);
            $properties->setDescription($metadata['subject']);
            $properties->setSubject($metadata['subject']);
            $properties->setKeywords($metadata['keywords']);
            $properties->setCompany($metadata['author']);

            // Configure default font
            $phpWord->setDefaultFontName('Calibri');
            $phpWord->setDefaultFontSize(11);

            // Create section with 1-inch margins
            $section = $phpWord->addSection([
                'marginLeft' => 1440,
                'marginRight' => 1440,
                'marginTop' => 1440,
                'marginBottom' => 1440,
            ]);

            // Add footer with page numbers
            $footer = $section->addFooter();
            $footer->addPreserveText(
                'Page {PAGE} of {NUMPAGES}',
                ['alignment' => 'center', 'size' => 9, 'color' => '7f8c8d']
            );

            // Use PHPWord's HTML parser to add content
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $htmlContent, false, false);

            // Generate to memory
            $tempFile = tempnam(sys_get_temp_dir(), 'phpword_');
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempFile);

            // Verify file was created and is valid
            if (!file_exists($tempFile) || filesize($tempFile) === 0) {
                throw new \RuntimeException('Failed to generate valid DOCX file');
            }

            $fileData = file_get_contents($tempFile);
            @unlink($tempFile);

            return [
                'status' => 'success',
                'fileData' => $fileData
            ];
        } catch (\Throwable $e) {
            log_message('error', '[DocumentService] PHPWord error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'DOCX generation failed: ' . $e->getMessage()];
        }
    }

    /**
     * Wraps the HTML content with a styled HTML skeleton.
     *
     * @param string $htmlContent The body content.
     * @param string $title The document title.
     * @return string The full HTML string.
     */
    private function _getStyledHtml(string $htmlContent, string $title = 'Document'): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{$safeTitle}</title>
    <style>
        /* Professional Typography Hierarchy */
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #2c3e50;
            margin: 1in;
            max-width: 100%;
        }

        h1 {
            font-family: Calibri, Arial, sans-serif;
            font-size: 22pt;
            font-weight: 700;
            color: #1a1a1a;
            margin: 24pt 0 12pt 0;
            padding-bottom: 8pt;
            border-bottom: 2px solid #3498db;
        }

        h2 {
            font-family: Calibri, Arial, sans-serif;
            font-size: 16pt;
            font-weight: 600;
            color: #2c3e50;
            margin: 18pt 0 10pt 0;
        }

        h3 {
            font-family: Calibri, Arial, sans-serif;
            font-size: 13pt;
            font-weight: 600;
            color: #34495e;
            margin: 14pt 0 8pt 0;
        }

        /* Professional Paragraph Spacing */
        p {
            margin: 0 0 10pt 0;
            text-align: left;
        }

        /* Enhanced Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 16pt 0;
            font-size: 10pt;
        }

        thead {
            background-color: #34495e;
            color: white;
            font-weight: 600;
        }

        th, td {
            border: 1px solid #bdc3c7;
            padding: 8pt 10pt;
            text-align: left;
        }

        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        /* Professional Code Blocks */
        pre {
            background: #f4f4f4;
            border-left: 4px solid #3498db;
            padding: 12pt;
            margin: 12pt 0;
            font-family: "Courier New", monospace;
            font-size: 9pt;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        code {
            background: #ecf0f1;
            padding: 2pt 4pt;
            border-radius: 3px;
            font-family: "Courier New", monospace;
            font-size: 9pt;
        }

        pre code {
            background: none;
            padding: 0;
        }

        /* Enhanced Blockquotes */
        blockquote {
            border-left: 4px solid #95a5a6;
            margin: 12pt 0;
            padding: 8pt 12pt;
            background: #f8f9fa;
            font-style: italic;
            color: #555;
        }

        /* Lists */
        ul, ol {
            margin: 10pt 0;
            padding-left: 30pt;
        }

        li {
            margin: 4pt 0;
        }

        /* Links */
        a {
            color: #3498db;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Horizontal Rules */
        hr {
            border: none;
            border-top: 1px solid #bdc3c7;
            margin: 16pt 0;
        }

        /* Print-specific adjustments */
        @media print {
            body {
                margin: 0.5in;
            }
        }
    </style>
</head>
<body>{$htmlContent}</body>
</html>
HTML;
    }
}
