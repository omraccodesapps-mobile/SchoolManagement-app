<?php

namespace App\Service\Video;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * FFmpeg Transcoding Service
 * 
 * Handles video transcoding to multiple resolutions for adaptive streaming.
 * Uses FFmpeg for all video processing operations.
 */
class VideoTranscodingService
{
    private string $ffmpegPath;
    private string $ffprobePath;
    private LoggerInterface $logger;
    private array $resolutions;

    public function __construct(
        string $ffmpegPath,
        string $ffprobePath,
        LoggerInterface $logger,
        array $resolutions = ['360p', '720p', '1080p']
    ) {
        $this->ffmpegPath = $ffmpegPath;
        $this->ffprobePath = $ffprobePath;
        $this->logger = $logger;
        $this->resolutions = $resolutions;
    }

    /**
     * Get video duration and metadata
     */
    public function getVideoInfo(string $filePath): array
    {
        try {
            $process = new Process([
                $this->ffprobePath,
                '-v',
                'error',
                '-show_entries',
                'format=duration,size:stream=width,height,codec_name,codec_type',
                '-of',
                'json',
                $filePath,
            ]);

            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException("FFprobe failed: " . $process->getErrorOutput());
            }

            $output = json_decode($process->getOutput(), true);

            return [
                'duration' => (int) $output['format']['duration'] ?? 0,
                'size' => $output['format']['size'] ?? 0,
                'width' => $output['streams'][0]['width'] ?? 0,
                'height' => $output['streams'][0]['height'] ?? 0,
                'codec' => $output['streams'][0]['codec_name'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get video info: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Transcode video to specific resolution
     */
    public function transcodeToResolution(
        string $inputPath,
        string $outputPath,
        string $resolution,
        array $bitrates = []
    ): bool {
        try {
            // Parse resolution (e.g., "720p" -> 720)
            $heightMatch = [];
            preg_match('/(\d+)p/', $resolution, $heightMatch);
            $height = $heightMatch[1] ?? 720;

            // Get bitrate for resolution
            $bitrate = $bitrates[$resolution] ?? $this->getDefaultBitrate($resolution);

            $this->logger->info("Transcoding to {$resolution} ({$height}p) with bitrate {$bitrate}");

            $process = new Process([
                $this->ffmpegPath,
                '-i',
                $inputPath,
                '-vf',
                "scale=-1:{$height}",
                '-c:v',
                'libx264',
                '-preset',
                'medium',
                '-b:v',
                $bitrate,
                '-c:a',
                'aac',
                '-b:a',
                '128k',
                '-movflags',
                '+faststart',
                '-y',
                $outputPath,
            ]);

            // Transcoding can take a while
            $process->setTimeout(3600);
            $process->run();

            if (!$process->isSuccessful()) {
                $this->logger->error("Transcode failed: " . $process->getErrorOutput());
                return false;
            }

            $this->logger->info("Successfully transcoded to {$resolution}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Transcoding exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate thumbnail from video
     */
    public function generateThumbnail(
        string $inputPath,
        string $outputPath,
        int $timestampSeconds = 0
    ): bool {
        try {
            $this->logger->info("Generating thumbnail at {$timestampSeconds}s");

            $process = new Process([
                $this->ffmpegPath,
                '-i',
                $inputPath,
                '-ss',
                (string) $timestampSeconds,
                '-vf',
                'scale=320:-1',
                '-vframes',
                '1',
                '-y',
                $outputPath,
            ]);

            $process->setTimeout(60);
            $process->run();

            if (!$process->isSuccessful()) {
                $this->logger->error("Thumbnail generation failed: " . $process->getErrorOutput());
                return false;
            }

            $this->logger->info("Thumbnail generated successfully");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Thumbnail generation exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract audio from video
     */
    public function extractAudio(string $inputPath, string $outputPath): bool
    {
        try {
            $this->logger->info("Extracting audio from video");

            $process = new Process([
                $this->ffmpegPath,
                '-i',
                $inputPath,
                '-q:a',
                '9',
                '-n',
                $outputPath,
            ]);

            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful()) {
                $this->logger->error("Audio extraction failed: " . $process->getErrorOutput());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error("Audio extraction exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get default bitrate for resolution
     */
    private function getDefaultBitrate(string $resolution): string
    {
        $bitrates = [
            '360p' => '500k',
            '480p' => '1000k',
            '720p' => '2500k',
            '1080p' => '5000k',
        ];

        return $bitrates[$resolution] ?? '2500k';
    }

    /**
     * Batch transcode to multiple resolutions
     */
    public function batchTranscode(
        string $inputPath,
        string $outputDir,
        array $resolutions = null,
        array $bitrates = []
    ): array {
        $resolutions = $resolutions ?? $this->resolutions;
        $results = [];

        foreach ($resolutions as $resolution) {
            $outputPath = $outputDir . '/' . basename($inputPath, pathinfo($inputPath, PATHINFO_EXTENSION)) . "_{$resolution}.mp4";
            $success = $this->transcodeToResolution($inputPath, $outputPath, $resolution, $bitrates);

            $results[$resolution] = [
                'success' => $success,
                'output' => $outputPath,
            ];
        }

        return $results;
    }

    /**
     * Check if FFmpeg is available
     */
    public function isAvailable(): bool
    {
        $process = new Process([$this->ffmpegPath, '-version']);
        $process->setTimeout(5);

        try {
            $process->run();
            return $process->isSuccessful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
