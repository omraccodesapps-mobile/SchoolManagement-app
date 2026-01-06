<?php

namespace App\Service\Video;

use App\Entity\Video;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\VideoRepository;
use App\Service\Storage\MinIOService;
use App\Validator\VideoUploadValidator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Video Upload Service
 * 
 * Handles video file uploads, validation, and initial storage
 */
class VideoUploadService
{
    private VideoUploadValidator $validator;
    private MinIOService $minioService;
    private EntityManagerInterface $entityManager;
    private VideoRepository $videoRepository;
    private LoggerInterface $logger;
    private string $tempDir;
    private Filesystem $filesystem;

    public function __construct(
        VideoUploadValidator $validator,
        MinIOService $minioService,
        EntityManagerInterface $entityManager,
        VideoRepository $videoRepository,
        LoggerInterface $logger,
        string $tempDir = 'var/videos'
    ) {
        $this->validator = $validator;
        $this->minioService = $minioService;
        $this->entityManager = $entityManager;
        $this->videoRepository = $videoRepository;
        $this->logger = $logger;
        $this->tempDir = $tempDir;
        $this->filesystem = new Filesystem();
    }

    /**
     * Handle video file upload
     * 
     * @throws \Exception If validation fails
     */
    public function uploadVideo(
        UploadedFile $file,
        Course $course,
        User $teacher,
        string $title,
        string $description = ''
    ): Video {
        try {
            // Validate file
            $errors = $this->validator->validate($file);
            if (!empty($errors)) {
                throw new \InvalidArgumentException(implode(', ', $errors));
            }

            $this->logger->info("Starting video upload: {$title}");

            // Create temporary directory if needed
            if (!$this->filesystem->exists($this->tempDir)) {
                $this->filesystem->mkdir($this->tempDir, 0755);
            }

            // Create Video entity
            $video = new Video();
            $video->setCourse($course);
            $video->setUploadedBy($teacher);
            $video->setTitle($title);
            $video->setDescription($description);
            $video->setStatus('DRAFT');
            $video->setType('ON_DEMAND');

            // Move file to temp location
            $tempPath = $this->tempDir . '/' . $video->getId() . '_' . $file->getClientOriginalName();
            $file->move($this->tempDir, basename($tempPath));

            // Store temp path (will be used during processing)
            $video->setVideoUrl($tempPath);

            $this->entityManager->persist($video);
            $this->entityManager->flush();

            $this->logger->info("Video created with ID: {$video->getId()}");

            return $video;
        } catch (\Exception $e) {
            $this->logger->error("Video upload failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get upload progress (for resumable uploads)
     */
    public function getUploadProgress(Video $video): array
    {
        return [
            'id' => $video->getId(),
            'title' => $video->getTitle(),
            'status' => $video->getStatus(),
            'size' => $video->getVideoUrl() ? filesize($video->getVideoUrl()) : 0,
            'duration' => $video->getDuration(),
            'createdAt' => $video->getCreatedAt(),
        ];
    }

    /**
     * Delete uploaded video and all associated data
     */
    public function deleteVideo(Video $video): void
    {
        try {
            // Delete from MinIO
            foreach ($video->getVariants() as $variant) {
                $this->minioService->deleteObject($variant->getMinioPath(), 'video');
            }

            // Delete thumbnail
            if ($video->getThumbnailUrl()) {
                $thumbnailKey = 'thumbnails/' . $video->getId() . '.jpg';
                $this->minioService->deleteObject($thumbnailKey, 'thumbnail');
            }

            // Delete from database
            $this->entityManager->remove($video);
            $this->entityManager->flush();

            // Delete temp file if exists
            if ($video->getVideoUrl() && file_exists($video->getVideoUrl())) {
                unlink($video->getVideoUrl());
            }

            $this->logger->info("Video deleted: {$video->getId()}");
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete video: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get video streaming URL for a specific resolution
     */
    public function getStreamingUrl(Video $video, string $resolution = '720p'): ?string
    {
        $variant = $video->getVariants()->filter(function ($v) use ($resolution) {
            return $v->getResolution() === $resolution && $v->getStatus() === 'READY';
        })->first();

        if ($variant) {
            return $this->minioService->getStreamingUrl($variant->getMinioPath(), 'video');
        }

        return null;
    }

    /**
     * Get all available streams for a video
     */
    public function getAvailableStreams(Video $video): array
    {
        $streams = [];

        foreach ($video->getVariants() as $variant) {
            if ($variant->getStatus() === 'READY') {
                $streams[] = [
                    'resolution' => $variant->getResolution(),
                    'bitrate' => $variant->getBitrate(),
                    'url' => $this->minioService->getStreamingUrl($variant->getMinioPath(), 'video'),
                    'fileSize' => $variant->getFileSize(),
                ];
            }
        }

        return $streams;
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl(Video $video): ?string
    {
        return $video->getThumbnailUrl();
    }
}
