<?php

declare(strict_types=1);

namespace App\Modules\Ollama\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;
use Parsedown;
use App\Modules\Ollama\Libraries\PandocService;

/**
 * Ollama Document Service
 *
 * Generates professional documents (PDF/DOCX) from AI-generated markdown content.
 * Implements a multi-tier fallback strategy for maximum compatibility across environments.
 *
 * Generation Strategy (Priority Order):
 * 1. Pandoc + XeLaTeX (Primary): High-fidelity, production-grade PDFs with full Unicode support
 * 2. Dompdf (PDF Fallback): Pure PHP solution, no system dependencies required
 * 3. PHPWord (DOCX Fallback): Pure PHP Word document generation with workarounds for edge cases
 *
 * Refactoring Notes:
 * - Uses PHP 8.0 match expression for cleaner format selection (replaced if-elseif chain)
 * - Implements constructor property promotion for dependency injection
 * - Maintains all existing workarounds for PHPWord (ampersand bug, table nesting, code blocks)
 *
 * @package App\Modules\Ollama\Libraries
 */
class OllamaDocumentService
{
    /**
     * Constructor with Property Promotion (PHP 8.0+)
     *
     * @param PandocService $pandocService Service for invoking Pandoc/XeLaTeX engine
     */
    public function __construct(
        protected PandocService $pandocService = new PandocService()
    ) {}

    /**
     * Generate Document with Multi-Tier Fallback Strategy
     *
     * Attempts Pandoc first for highest quality, then falls back to pure PHP solutions
     * (Dompdf for PDF, PHPWord for DOCX) if Pandoc is unavailable or fails.
     *
     * Refactoring: Uses PHP 8.0 match expression (line 50-57) for format selection,
     * replacing the previous if-elseif block for improved readability and exhaustiveness checking.
     *
     * @param string $markdownContent Raw markdown text from AI response
     * @param string $format Target format: 'pdf' or 'docx'
     * @param array $metadata Document metadata (title, author, subject, keywords, creator)
     * @return array ['status' => 'success'|'error', 'fileData' => string|null, 'message' => string|null]
     */
    public function generate(string $markdownContent, string $format, array $metadata = []): array
    {
        // Merge user metadata with sensible defaults
        $defaults = [
            'title' => 'Ollama Generated Document',
            'author' => 'Ollama Assistant',
            'subject' => 'Generated Content',
            'keywords' => 'AI, Content, Report, Ollama',
            'creator' => 'Ollama Assistant',
        ];
        $meta = array_merge($defaults, $metadata);

        // Convert Markdown to HTML (Parsedown library)
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        $parsedown->setBreaksEnabled(true);
        $htmlContent = $parsedown->text($markdownContent);
        $styledHtml = $this->_getStyledHtml($htmlContent, $meta['title']);

        // Strategy 1: Try Pandoc (Primary - highest quality)
        if ($this->pandocService->isAvailable()) {
            $pandocResult = $this->pandocService->generate($styledHtml, $format, 'ollama_temp_' . bin2hex(random_bytes(8)));

            if ($pandocResult['status'] === 'success' && file_exists($pandocResult['filePath'])) {
                // Read, delete temp file, return binary data (ephemeral storage pattern)
                $fileData = file_get_contents($pandocResult['filePath']);
                @unlink($pandocResult['filePath']);

                return [
                    'status' => 'success',
                    'fileData' => $fileData
                ];
            }

            log_message('warning', '[OllamaDocumentService] Pandoc failed: ' . ($pandocResult['message'] ?? 'Unknown') . '. Attempting fallback.');
        }

        // Strategy 2 & 3: PHP Fallbacks (match expression - refactored from if-elseif)
        // Match provides exhaustive pattern matching with cleaner syntax
        return match ($format) {
            'pdf' => $this->_generateWithDompdf($htmlContent, $meta),
            'docx' => $this->_generateWithPHPWord($markdownContent, $meta),
            default => [
                'status' => 'error',
                'message' => 'Could not generate document. Unsupported format or all converters failed.'
            ]
        };
    }

    private function _generateWithDompdf(string $htmlContent, array $metadata): array
    {
        try {
            $options = new Options();
            $options->set('defaultFont', 'Georgia');
            $options->set('isRemoteEnabled', true);
            $options->set('isFontSubsettingEnabled', false);
            $options->set('defaultPaperSize', 'letter');
            $options->set('defaultPaperOrientation', 'portrait');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', false);

            $userId = session()->get('userId') ?? 0;
            $tempDir = WRITEPATH . 'uploads/dompdf_temp/' . $userId;
            if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
            $options->set('tempDir', $tempDir);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($htmlContent, 'UTF-8');
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->render();

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
            log_message('error', '[OllamaDocumentService] Dompdf error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'PDF generation failed.'];
        }
    }

    private function _generateWithPHPWord(string $markdownContent, array $metadata): array
    {
        try {
            $markdownContent = preg_replace('/^[\t ]+\|/m', '|', $markdownContent);
            $markdownContent = preg_replace('/^(?!\|)(.*)\n\|/m', "$1\n\n|", $markdownContent);

            $parsedown = new Parsedown();
            $parsedown->setSafeMode(true);
            $parsedown->setBreaksEnabled(true);
            $htmlContent = $parsedown->text($markdownContent);

            $phpWord = new \PhpOffice\PhpWord\PhpWord();

            $properties = $phpWord->getDocInfo();
            $properties->setCreator($metadata['creator']);
            $properties->setTitle($metadata['title']);
            $properties->setDescription($metadata['subject']);
            $properties->setSubject($metadata['subject']);
            $properties->setKeywords($metadata['keywords']);
            $properties->setCompany($metadata['author']);

            $phpWord->setDefaultFontName('Calibri');
            $phpWord->setDefaultFontSize(11);

            $section = $phpWord->addSection([
                'marginLeft' => 1440,
                'marginRight' => 1440,
                'marginTop' => 1440,
                'marginBottom' => 1440,
            ]);

            $footer = $section->addFooter();
            $footer->addPreserveText(
                'Page {PAGE} of {NUMPAGES}',
                ['alignment' => 'center', 'size' => 9, 'color' => '7f8c8d']
            );

            $fixedHtml = str_replace('&', '&amp;', $htmlContent);

            $fixedHtml = preg_replace_callback('/<pre><code(.*?)>(.*?)<\/code><\/pre>/s', function ($matches) {
                $codeContent = $matches[2];
                $codeContent = str_replace(["\r\n", "\r"], "\n", $codeContent);
                $codeContent = str_replace("\n", '<br/>', $codeContent);
                $codeContent = str_replace(' ', '&nbsp;', $codeContent);
                return '<pre><code' . $matches[1] . ' style="font-family: \'Courier New\'; font-size: 9pt;">' . $codeContent . '</code></pre>';
            }, $fixedHtml);

            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $fixedHtml, false, false);

            $tempFile = tempnam(sys_get_temp_dir(), 'phpword_');
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempFile);

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
            log_message('error', '[OllamaDocumentService] PHPWord error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'DOCX generation failed: ' . $e->getMessage()];
        }
    }

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

        p {
            margin: 0 0 10pt 0;
            text-align: left;
        }

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

        blockquote {
            border-left: 4px solid #95a5a6;
            margin: 12pt 0;
            padding: 8pt 12pt;
            background: #f8f9fa;
            font-style: italic;
            color: #555;
        }

        ul, ol {
            margin: 10pt 0;
            padding-left: 30pt;
        }

        li {
            margin: 4pt 0;
        }

        a {
            color: #3498db;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        hr {
            border: none;
            border-top: 1px solid #bdc3c7;
            margin: 16pt 0;
        }

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
