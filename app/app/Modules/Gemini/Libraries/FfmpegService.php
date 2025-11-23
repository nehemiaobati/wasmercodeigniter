<?php declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

class FfmpegService
{
    /**
     * Main Entry Point: Orchestrates the conversion.
     * 
     * @param string $base64Data Raw PCM data from Gemini
     * @param string $outputDir The directory to save the file
     * @param string $filenameBase The filename WITHOUT extension (e.g., 'speech_123')
     * @return array{success: bool, fileName: string|null}
     */
    public function processAudio(string $base64Data, string $outputDir, string $filenameBase): array
    {
        // 1. Try FFmpeg (MP3) - Preferred for size
        if ($this->isAvailable()) {
            $fileName = $filenameBase . '.mp3';
            $fullPath = $outputDir . $fileName;
            
            if ($this->convertPcmToMp3($base64Data, $fullPath)) {
                return ['success' => true, 'fileName' => $fileName];
            }
            // If FFmpeg fails mid-process, log it and fall through to WAV
            log_message('error', '[FfmpegService] MP3 conversion failed. Falling back to WAV.');
        }

        // 2. Fallback (WAV) - Native PHP, larger file size but guaranteed to work
        $fileName = $filenameBase . '.wav';
        $fullPath = $outputDir . $fileName;
        
        if ($this->createWavFile($base64Data, $fullPath)) {
             return ['success' => true, 'fileName' => $fileName];
        }

        return ['success' => false, 'fileName' => null];
    }

    public function isAvailable(): bool
    {
        return !empty(shell_exec('command -v ffmpeg 2>/dev/null'));
    }

    /**
     * Strategy A: FFmpeg Pipe (Memory Efficient)
     */
    private function convertPcmToMp3(string $base64Data, string $outputFile): bool
    {
        $cmd = sprintf(
            'ffmpeg -f s16le -ar 24000 -ac 1 -i pipe:0 -y -vn -acodec libmp3lame -b:a 128k %s 2>/dev/null',
            escapeshellarg($outputFile)
        );

        $process = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], base64_decode($base64Data));
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            return (proc_close($process) === 0 && file_exists($outputFile));
        }

        return false;
    }

    /**
     * Strategy B: Native PHP WAV Header (Reliable Fallback)
     */
    private function createWavFile(string $base64Data, string $outputFile): bool
    {
        $pcmData = base64_decode($base64Data);
        $len = strlen($pcmData);
        
        // Build standard RIFF WAVE header for Gemini specs (24kHz, 16-bit, Mono)
        $header = 'RIFF' . pack('V', 36 + $len) . 'WAVE';
        $header .= 'fmt ' . pack('V', 16); // Subchunk1Size
        $header .= pack('v', 1);           // AudioFormat (1 = PCM)
        $header .= pack('v', 1);           // NumChannels (1 = Mono)
        $header .= pack('V', 24000);       // SampleRate
        $header .= pack('V', 48000);       // ByteRate (SampleRate * NumChannels * BitsPerSample/8)
        $header .= pack('v', 2);           // BlockAlign
        $header .= pack('v', 16);          // BitsPerSample
        $header .= 'data' . pack('V', $len);

        return file_put_contents($outputFile, $header . $pcmData) !== false;
    }
}
