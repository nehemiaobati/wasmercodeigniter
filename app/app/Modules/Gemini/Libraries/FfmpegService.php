<?php declare(strict_types=1);

namespace App\Modules\Gemini\Libraries;

use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Format\Audio\DefaultAudio;

/**
 * A service wrapper for the FFmpeg utility to handle audio conversions.
 * This service specifically handles the conversion of raw PCM audio data
 * from the Google Gemini TTS API into a web-playable MP3 format.
 */
class FfmpegService
{
    /**
     * @var FFMpeg|null
     */
    private ?FFMpeg $ffmpeg = null;

    /**
     * Constructor.
     * Initializes the FFmpeg library. It automatically locates the `ffmpeg` and
     * `ffprobe` binaries. Throws an exception if they cannot be found.
     */
    public function __construct()
    {
        try {
            // The create() method will automatically attempt to locate FFmpeg.
            // On a properly configured server (e.g., with FFmpeg in the system's PATH),
            // no configuration array is needed.
            $this->ffmpeg = FFMpeg::create();
        } catch (\Throwable $e) {
            // This will catch any error, including the "Unable to fork" ErrorException.
            log_message('error', '[FfmpegService] Failed to initialize FFmpeg. The exec() function may be disabled or FFmpeg not installed. Error: ' . $e->getMessage());
            $this->ffmpeg = null; // Ensure ffmpeg property is null on failure.
        }
    }

    /**
     * Converts a raw PCM audio file to an MP3 file.
     *
     * This method is specifically tuned for the output format of the Gemini TTS API,
     * which is raw PCM data (s16le codec, 24000Hz sample rate, 1 audio channel).
     *
     * @param string $rawFilePath The absolute path to the input temporary .raw file.
     * @param string $mp3FilePath The absolute path where the output .mp3 file will be saved.
     * @return bool True on successful conversion, false on failure.
     */
    public function convertPcmToMp3(string $rawFilePath, string $mp3FilePath): bool
    {
        // Check if FFmpeg was successfully initialized in the constructor.
        if ($this->ffmpeg === null) {
            log_message('error', '[FfmpegService] Cannot convert audio because FFmpeg is not available.');
            return false;
        }

        try {
            // Use the ffmpeg binary directly to convert raw PCM to MP3, avoiding incompatible library method signatures.
            $input  = escapeshellarg($rawFilePath);
            $output = escapeshellarg($mp3FilePath);

            // -f s16le : signed 16-bit little-endian PCM
            // -ar 24000 : sample rate 24000 Hz
            // -ac 1 : mono
            // -y : overwrite output if exists
            // -vn : no video
            // -acodec libmp3lame -b:a 128k : encode to MP3 at 128 kbps
            $cmd = sprintf(
                'ffmpeg -f s16le -ar 24000 -ac 1 -i %s -y -vn -acodec libmp3lame -b:a 128k %s 2>&1',
                $input,
                $output
            );

            exec($cmd, $outputLines, $returnVar);

            if ($returnVar !== 0) {
                log_message('error', '[FfmpegService] ffmpeg failed: ' . implode("\n", $outputLines));
                return false;
            }

            return file_exists($mp3FilePath);
        } catch (\Throwable $e) { // Broaden catch to Throwable
            log_message('error', '[FfmpegService] Conversion failed: ' . $e->getMessage());
            return false;
        }
    }
}
