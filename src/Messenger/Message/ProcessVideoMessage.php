<?php

namespace App\Messenger\Message;

use Ramsey\Uuid\UuidInterface;

/**
 * Message to process video transcoding asynchronously
 */
final class ProcessVideoMessage
{
    private UuidInterface $videoId;
    private array $options;

    public function __construct(UuidInterface $videoId, array $options = [])
    {
        $this->videoId = $videoId;
        $this->options = $options;
    }

    public function getVideoId(): UuidInterface
    {
        return $this->videoId;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
