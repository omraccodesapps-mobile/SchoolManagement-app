<?php

namespace App\Service\Storage;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * MinIO Service - S3-compatible object storage for videos
 * 
 * Manages all video storage operations using MinIO as the backend.
 * 100% self-hosted, no external cloud dependencies.
 */
class MinIOService
{
    private S3Client $client;
    private string $videoBucket;
    private string $thumbnailBucket;
    private string $region;
    private string $endpoint;
    private LoggerInterface $logger;

    public function __construct(
        string $endpoint,
        string $rootUser,
        string $rootPassword,
        string $region,
        string $videoBucket,
        string $thumbnailBucket,
        LoggerInterface $logger
    ) {
        $this->endpoint = $endpoint;
        $this->region = $region;
        $this->videoBucket = $videoBucket;
        $this->thumbnailBucket = $thumbnailBucket;
        $this->logger = $logger;

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $rootUser,
                'secret' => $rootPassword,
            ],
        ]);

        $this->ensureBucketsExist();
    }

    /**
     * Ensure buckets exist, create if needed
     */
    public function ensureBucketsExist(): void
    {
        foreach ([$this->videoBucket, $this->thumbnailBucket] as $bucket) {
            try {
                $this->client->headBucket(['Bucket' => $bucket]);
            } catch (AwsException $e) {
                if ($e->getAwsErrorCode() === 404) {
                    try {
                        $this->client->createBucket(['Bucket' => $bucket]);
                        $this->logger->info("Created MinIO bucket: {$bucket}");
                    } catch (AwsException $createError) {
                        $this->logger->error("Failed to create bucket {$bucket}: " . $createError->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Upload a file to MinIO
     */
    public function uploadFile(
        string $filePath,
        string $objectName,
        string $bucketType = 'video'
    ): string {
        try {
            $bucket = $bucketType === 'thumbnail' ? $this->thumbnailBucket : $this->videoBucket;

            $this->client->putObject([
                'Bucket' => $bucket,
                'Key' => $objectName,
                'SourceFile' => $filePath,
            ]);

            $this->logger->info("Uploaded {$objectName} to {$bucket}");
            return $objectName;
        } catch (AwsException $e) {
            $this->logger->error("Upload failed: " . $e->getMessage());
            throw new \RuntimeException("Failed to upload file: " . $e->getMessage());
        }
    }

    /**
     * Upload from stream
     */
    public function uploadStream(
        $stream,
        string $objectName,
        string $bucketType = 'video'
    ): string {
        try {
            $bucket = $bucketType === 'thumbnail' ? $this->thumbnailBucket : $this->videoBucket;

            $this->client->putObject([
                'Bucket' => $bucket,
                'Key' => $objectName,
                'Body' => $stream,
            ]);

            $this->logger->info("Uploaded stream {$objectName} to {$bucket}");
            return $objectName;
        } catch (AwsException $e) {
            $this->logger->error("Stream upload failed: " . $e->getMessage());
            throw new \RuntimeException("Failed to upload stream: " . $e->getMessage());
        }
    }

    /**
     * Get a temporary download URL
     */
    public function getPresignedUrl(
        string $objectName,
        int $expiresIn = 3600,
        string $bucketType = 'video'
    ): string {
        try {
            $bucket = $bucketType === 'thumbnail' ? $this->thumbnailBucket : $this->videoBucket;

            $cmd = $this->client->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $objectName,
            ]);

            $request = $this->client->createPresignedRequest($cmd, "+{$expiresIn} seconds");
            $url = (string) $request->getUri();

            return $url;
        } catch (AwsException $e) {
            $this->logger->error("Failed to generate presigned URL: " . $e->getMessage());
            throw new \RuntimeException("Failed to generate presigned URL: " . $e->getMessage());
        }
    }

    /**
     * Delete an object from MinIO
     */
    public function deleteObject(string $objectName, string $bucketType = 'video'): void
    {
        try {
            $bucket = $bucketType === 'thumbnail' ? $this->thumbnailBucket : $this->videoBucket;

            $this->client->deleteObject([
                'Bucket' => $bucket,
                'Key' => $objectName,
            ]);

            $this->logger->info("Deleted {$objectName} from {$bucket}");
        } catch (AwsException $e) {
            $this->logger->error("Delete failed: " . $e->getMessage());
            throw new \RuntimeException("Failed to delete object: " . $e->getMessage());
        }
    }

    /**
     * Check if object exists
     */
    public function objectExists(string $objectName, string $bucketType = 'video'): bool
    {
        try {
            $bucket = $bucketType === 'thumbnail' ? $this->thumbnailBucket : $this->videoBucket;
            return $this->client->doesObjectExist($bucket, $objectName);
        } catch (AwsException $e) {
            $this->logger->error("Check existence failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get object metadata (size, etc.)
     */
    public function getObjectMetadata(string $objectName, string $bucketType = 'video'): array
    {
        try {
            $bucket = $bucketType === 'thumbnail' ? $this->thumbnailBucket : $this->videoBucket;

            $result = $this->client->headObject([
                'Bucket' => $bucket,
                'Key' => $objectName,
            ]);

            return [
                'size' => $result['ContentLength'] ?? 0,
                'lastModified' => $result['LastModified'] ?? null,
                'contentType' => $result['ContentType'] ?? 'application/octet-stream',
            ];
        } catch (AwsException $e) {
            $this->logger->error("Failed to get metadata: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get streaming URL (for HLS/DASH)
     */
    public function getStreamingUrl(string $objectName, string $bucketType = 'video'): string
    {
        $bucket = $bucketType === 'thumbnail' ? $this->thumbnailBucket : $this->videoBucket;
        return "{$this->endpoint}/{$bucket}/{$objectName}";
    }

    /**
     * List objects in bucket
     */
    public function listObjects(string $prefix = '', string $bucketType = 'video'): array
    {
        try {
            $bucket = $bucketType === 'thumbnail' ? $this->thumbnailBucket : $this->videoBucket;

            $result = $this->client->listObjects([
                'Bucket' => $bucket,
                'Prefix' => $prefix,
            ]);

            return $result['Contents'] ?? [];
        } catch (AwsException $e) {
            $this->logger->error("List failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get bucket name
     */
    public function getVideoBucket(): string
    {
        return $this->videoBucket;
    }

    public function getThumbnailBucket(): string
    {
        return $this->thumbnailBucket;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
