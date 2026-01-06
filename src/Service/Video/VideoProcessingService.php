<?php

namespace App\Service\Video;

use App\Entity\Video;
use App\Entity\VideoVariant;
use App\Repository\VideoRepository;
use App\Service\Storage\MinIOService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Video Processing Service
 * 
 * Orchestrates the entire video processing pipeline:
 * 1. Validate upload
 * 2. Transcode to multiple resolutions
 * 3. Generate thumbnails
 * 4. Upload to MinIO
 * 5. Update database
 * 6. Cleanup temporary files
 */
class VideoProcessingService
{
    private VideoTranscodingService $transcodingService;
    private MinIOService $minioService;
    private EntityManagerInterface $entityManager;
    private VideoRepository $videoRepository;
    private LoggerInterface $logger;
    private string $tempDir;
    private Filesystem $filesystem;

    public function __construct(
        VideoTranscodingService $transcodingService,
        MinIOService $minioService,
        EntityManagerInterface $entityManager,
        VideoRepository $videoRepository,
        LoggerInterface $logger,
        string $tempDir = 'var/videos'
    ) {
        $this->transcodingService = $transcodingService;
        $this->minioService = $minioService;
        $this->entityManager = $entityManager;
        $this->videoRepository = $videoRepository;
        $this->logger = $logger;
        $this->tempDir = $tempDir;
        $this->filesystem = new Filesystem();
    }

    /**
     * Process uploaded video
     * 
     * This is typically called from a message handler for async processing
     */
    public function processVideo(Video $video, array $options = []): bool
    {
        try {
            $this->logger->info("Starting video processing for: {$video->getTitle()}");

            // Update status to processing
            $video->setStatus('PROCESSING');
            $this->entityManager->flush();

            // Get video info
            if (!$this->transcodingService->isAvailable()) {
                throw new \RuntimeException('FFmpeg is not available');
            }

            // Prepare temp directory
            $this->prepareTempDirectory();

            // Generate thumbnail
            $this->generateAndUploadThumbnail($video);

            // Transcode to multiple resolutions
            $resolutions = $options['resolutions'] ?? ['360p', '720p', '1080p'];
            $this->transcodeAndUpload($video, $resolutions);

            // Mark as ready
            $video->setStatus('READY');
            $this->entityManager->flush();

            $this->logger->info("Video processing completed successfully");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Video processing failed: " . $e->getMessage());
            $video->setStatus('FAILED');
            $this->entityManager->flush();
            return false;
        } finally {
            $this->cleanupTemporaryFiles();
        }
    }

    /**
     * Generate thumbnail and upload to MinIO
     */
    private function generateAndUploadThumbnail(Video $video): void
    {
        try {
            $sourceFile = $video->getVideoUrl(); // Local temp file
            $thumbnailPath = $this->tempDir . '/thumb_' . $video->getId() . '.jpg';

            if (!$this->transcodingService->generateThumbnail($sourceFile, $thumbnailPath, 2)) {
                $this->logger->warning("Failed to generate thumbnail");
                return;
            }

            // Upload thumbnail to MinIO
            $thumbnailKey = 'thumbnails/' . $video->getId() . '.jpg';
            $this->minioService->uploadFile($thumbnailPath, $thumbnailKey, 'thumbnail');

            // Update video with thumbnail URL
            $thumbnailUrl = $this->minioService->getStreamingUrl($thumbnailKey, 'thumbnail');
            $video->setThumbnailUrl($thumbnailUrl);

            $this->logger->info("Thumbnail uploaded: {$thumbnailKey}");
        } catch (\Exception $e) {
            $this->logger->warning("Thumbnail generation/upload failed: " . $e->getMessage());
            // Don't fail the entire process if thumbnail fails
        }
    }

    /**
     * Transcode video and upload variants
     */
    private function transcodeAndUpload(Video $video, array $resolutions): void
    {
        foreach ($resolutions as $resolution) {
            try {
                $outputPath = $this->tempDir . '/' . $video->getId() . "_{$resolution}.mp4";

                // Create variant entity
                $variant = new VideoVariant();
                $variant->setVideo($video);
                $variant->setResolution($resolution);
                $variant->setStatus('PENDING');

                // Extract bitrate from resolution
                $bitrates = [
                    '360p' => '500k',
                    '720p' => '2500k',
                    '1080p' => '5000k',
                ];
                $bitrate = $bitrates[$resolution] ?? '2500k';
                $variant->setBitrate($bitrate);

                $this->entityManager->persist($variant);
                $this->entityManager->flush();

                // Transcode
                $success = $this->transcodingService->transcodeToResolution(
                    $video->getVideoUrl(),
                    $outputPath,
                    $resolution,
                    $bitrates
                );

                if (!$success) {
                    $variant->setStatus('FAILED');
                    $this->entityManager->flush();
                    continue;
                }

                // Upload to MinIO
                $key = "videos/{$video->getId()}/{$resolution}.mp4";
                $this->minioService->uploadFile($outputPath, $key, 'video');

                // Get file size
                $fileSize = filesize($outputPath);

                // Update variant
                $variant->setMinioPath($key);
                $variant->setFileSize($fileSize);
                $variant->setStatus('READY');
                $this->entityManager->flush();

                $this->logger->info("Uploaded variant: {$resolution} ({$fileSize} bytes)");
            } catch (\Exception $e) {
                $this->logger->error("Transcode/upload failed for {$resolution}: " . $e->getMessage());
                if (isset($variant)) {
                    $variant->setStatus('FAILED');
                    $this->entityManager->flush();
                }
            }
        }
    }

    /**
     * Prepare temporary directory
     */
    private function prepareTempDirectory(): void
    {
        if (!$this->filesystem->exists($this->tempDir)) {
            $this->filesystem->mkdir($this->tempDir, 0755);
        }
    }

    /**
     * Clean up temporary files
     */
    private function cleanupTemporaryFiles(): void
    {
        try {
            if ($this->filesystem->exists($this->tempDir)) {
                $this->filesystem->remove($this->tempDir);
                $this->logger->info("Temporary files cleaned up");
            }
        } catch (\Exception $e) {
            $this->logger->warning("Failed to cleanup temporary files: " . $e->getMessage());
        }
    }

    /**
     * Get streaming URL for a video variant
     */
    public function getStreamingUrl(Video $video, string $resolution = '720p'): string
    {
        $variant = $video->getVariants()->filter(function (VideoVariant $v) use ($resolution) {
            return $v->getResolution() === $resolution && $v->getStatus() === 'READY';
        })->first();

        if (!$variant) {
            // Fallback to first available variant
            $variant = $video->getVariants()->filter(function (VideoVariant $v) {
                return $v->getStatus() === 'READY';
            })->first();
        }

        if ($variant) {
            return $this->minioService->getStreamingUrl($variant->getMinioPath(), 'video');
        }

        return '';
    }

    /**
     * Get all available qualities for a video
     */
    public function getAvailableQualities(Video $video): array
    {
        $qualities = [];

        foreach ($video->getVariants() as $variant) {
            if ($variant->getStatus() === 'READY') {
                $qualities[] = [
                    'resolution' => $variant->getResolution(),
                    'bitrate' => $variant->getBitrate(),
                    'url' => $this->minioService->getStreamingUrl($variant->getMinioPath(), 'video'),
                ];
            }
        }

        return $qualities;
    }
}
