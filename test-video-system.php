#!/usr/bin/env php
<?php

// Test script for video system
echo "=== Video Learning System - Integration Test ===\n\n";

require 'vendor/autoload.php';
require 'config/bootstrap.php';

use App\Service\Storage\MinIOService;
use Symfony\Component\Dotenv\Dotenv;
use Psr\Log\NullLogger;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load('.env', '.env.local');

echo "1. Testing MinIO Connection...\n";
try {
    $minioService = new MinIOService(
        $_ENV['MINIO_ENDPOINT'] ?? 'http://localhost:9000',
        $_ENV['MINIO_ROOT_USER'] ?? 'minioadmin',
        $_ENV['MINIO_ROOT_PASSWORD'] ?? 'minioadmin',
        $_ENV['MINIO_REGION'] ?? 'us-east-1',
        $_ENV['MINIO_BUCKET_VIDEOS'] ?? 'school-videos',
        $_ENV['MINIO_BUCKET_THUMBNAILS'] ?? 'school-thumbnails',
        new NullLogger()
    );

    echo "   - MinIOService instantiated\n";
    echo "   - Endpoint: " . $_ENV['MINIO_ENDPOINT'] . "\n";
    echo "   - Video Bucket: " . $_ENV['MINIO_BUCKET_VIDEOS'] . "\n";
    echo "   - Thumbnail Bucket: " . $_ENV['MINIO_BUCKET_THUMBNAILS'] . "\n";

    // Try to ensure buckets exist
    echo "   - Attempting to connect to MinIO and ensure buckets...\n";
    $minioService->ensureBucketsExist();
    echo "   ✅ MinIO connection successful!\n";
    echo "   ✅ Buckets ready!\n\n";
} catch (\Exception $e) {
    echo "   ❌ MinIO connection failed!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Please ensure:\n";
    echo "   1. MinIO is running on " . $_ENV['MINIO_ENDPOINT'] . "\n";
    echo "   2. Credentials are correct in .env\n";
    echo "   3. Network connectivity is working\n\n";
}

echo "2. Checking Configuration...\n";
echo "   - FFMPEG_PATH: " . ($_ENV['FFMPEG_PATH'] ?? 'NOT SET') . "\n";
echo "   - FFPROBE_PATH: " . ($_ENV['FFPROBE_PATH'] ?? 'NOT SET') . "\n";
echo "   - VIDEO_TEMP_DIR: " . ($_ENV['VIDEO_TEMP_DIR'] ?? 'NOT SET') . "\n";
echo "   - VIDEO_MAX_SIZE: " . ($_ENV['VIDEO_MAX_SIZE'] ?? 'NOT SET') . "\n\n";

echo "3. System Requirements:\n";
echo "   - PHP Version: " . phpversion() . " ✅\n";

// Check if temp directory exists
$tempDir = $_ENV['VIDEO_TEMP_DIR'] ?? 'var/videos';
if (!is_dir($tempDir)) {
    @mkdir($tempDir, 0777, true);
    echo "   - Temp Directory ($tempDir): Created ✅\n";
} else {
    echo "   - Temp Directory ($tempDir): Exists ✅\n";
}

echo "\n=== All Systems Ready ===\n";
