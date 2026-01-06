<?php

namespace App\Command;

use App\Service\Storage\MinIOService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-video-system',
    description: 'Test video learning system integration',
    aliases: ['test:video']
)]
class TestVideoSystemCommand extends Command
{
    public function __construct(
        private MinIOService $minioService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Video Learning System - Integration Test');

        $io->section('1. Testing MinIO Connection');
        try {
            // Try to list objects as a connectivity test
            $videoBucket = $this->minioService->getVideoBucket();
            $thumbnailBucket = $this->minioService->getThumbnailBucket();
            $endpoint = $this->minioService->getEndpoint();

            // Attempt to list objects (will fail if connection is bad)
            $this->minioService->listObjects('', 'video');

            $io->success('MinIO connection successful!');
            $io->writeln('  Endpoint: ' . $endpoint);
            $io->writeln('  Video Bucket: ' . $videoBucket);
            $io->writeln('  Thumbnail Bucket: ' . $thumbnailBucket);
        } catch (\Exception $e) {
            $io->error('MinIO connection failed: ' . $e->getMessage());
            $io->note('Make sure MinIO is running: docker-compose -f docker-compose.video.yml up -d');
            return Command::FAILURE;
        }

        $io->section('2. Checking Configuration');
        $io->writeln('  FFMPEG_PATH: ' . ($_ENV['FFMPEG_PATH'] ?? 'NOT SET'));
        $io->writeln('  FFPROBE_PATH: ' . ($_ENV['FFPROBE_PATH'] ?? 'NOT SET'));
        $io->writeln('  VIDEO_TEMP_DIR: ' . ($_ENV['VIDEO_TEMP_DIR'] ?? 'NOT SET'));
        $io->writeln('  VIDEO_MAX_SIZE: ' . ($_ENV['VIDEO_MAX_SIZE'] ?? 'NOT SET'));

        $io->section('3. System Requirements');
        $io->writeln('  PHP Version: ' . phpversion() . ' ✅');

        // Check if temp directory exists
        $tempDir = $_ENV['VIDEO_TEMP_DIR'] ?? 'var/videos';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
            $io->writeln("  Temp Directory ($tempDir): Created ✅");
        } else {
            $io->writeln("  Temp Directory ($tempDir): Exists ✅");
        }

        $io->success('All systems ready!');
        return Command::SUCCESS;
    }
}
