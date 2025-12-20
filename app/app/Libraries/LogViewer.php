<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Service for reading and listing application logs securely.
 */
class LogViewer
{
    private string $logPath;

    public function __construct()
    {
        $this->logPath = WRITEPATH . 'logs/';
        helper('filesystem');
    }

    /**
     * Returns a list of log files, sorted by date (newest first).
     */
    public function getLogFiles(): array
    {
        $files = get_filenames($this->logPath);
        if ($files) {
            rsort($files);
            return $files;
        }
        return [];
    }

    /**
     * Reads a specific log file safely.
     *
     * @param string $filename
     * @return string Log content or error message.
     */
    public function getLogContent(string $filename): string
    {
        // Sanitize filename to prevent directory traversal
        $safeFile = basename($filename);
        $fullFilePath = $this->logPath . $safeFile;

        if (file_exists($fullFilePath)) {
            $content = file_get_contents($fullFilePath);
            return $content !== false ? $content : "Error: Could not read log file.";
        }

        return "Error: File not found.";
    }
}
