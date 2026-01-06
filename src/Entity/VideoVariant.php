<?php

namespace App\Entity;

use App\Repository\VideoVariantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: VideoVariantRepository::class)]
#[ORM\Table(name: 'video_variant')]
class VideoVariant
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: Video::class, inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Video $video = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $resolution = null; // 360p, 720p, 1080p

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $bitrate = null; // 500k, 2500k, 5000k

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $fileSize = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $minioPath = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $status = 'PENDING'; // PENDING, READY, FAILED

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

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(string $resolution): static
    {
        $this->resolution = $resolution;

        return $this;
    }

    public function getBitrate(): ?string
    {
        return $this->bitrate;
    }

    public function setBitrate(string $bitrate): static
    {
        $this->bitrate = $bitrate;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getMinioPath(): ?string
    {
        return $this->minioPath;
    }

    public function setMinioPath(string $minioPath): static
    {
        $this->minioPath = $minioPath;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
