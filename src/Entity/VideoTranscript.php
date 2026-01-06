<?php

namespace App\Entity;

use App\Repository\VideoTranscriptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: VideoTranscriptRepository::class)]
#[ORM\Table(name: 'video_transcript')]
class VideoTranscript
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\OneToOne(targetEntity: Video::class, inversedBy: 'transcript')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Video $video = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $rawTranscript = null;

    #[ORM\Column(type: Types::JSON)]
    private array $segments = []; // [{timestamp, text, duration}, ...]

    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $language = 'en';

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $status = 'PENDING'; // PENDING, READY, FAILED

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $generatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getVideo(): ?Video
    {
        return $this->video;
    }

    public function setVideo(?Video $video): static
    {
        $this->video = $video;

        return $this;
    }

    public function getRawTranscript(): ?string
    {
        return $this->rawTranscript;
    }

    public function setRawTranscript(string $rawTranscript): static
    {
        $this->rawTranscript = $rawTranscript;

        return $this;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function setSegments(array $segments): static
    {
        $this->segments = $segments;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(?\DateTimeImmutable $generatedAt): static
    {
        $this->generatedAt = $generatedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
