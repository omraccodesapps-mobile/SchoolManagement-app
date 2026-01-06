<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\ProcessVideoMessage;
use App\Repository\VideoRepository;
use App\Service\Video\VideoProcessingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessVideoMessageHandler
{
    private VideoRepository $videoRepository;
    private VideoProcessingService $videoProcessingService;
    private LoggerInterface $logger;

    public function __construct(
        VideoRepository $videoRepository,
        VideoProcessingService $videoProcessingService,
        LoggerInterface $logger
    ) {
        $this->videoRepository = $videoRepository;
        $this->videoProcessingService = $videoProcessingService;
        $this->logger = $logger;
    }

    public function __invoke(ProcessVideoMessage $message): void
    {
        try {
            $video = $this->videoRepository->find($message->getVideoId());

            if (!$video) {
                $this->logger->error("Video not found: {$message->getVideoId()}");
                return;
            }

            $this->logger->info("Processing video: {$video->getTitle()}");
            $this->videoProcessingService->processVideo($video, $message->getOptions());
        } catch (\Exception $e) {
            $this->logger->error("Message handler exception: " . $e->getMessage());
            throw $e;
        }
    }
}
