<?php

namespace App\Entity;

use App\Repository\VideoProgressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: VideoProgressRepository::class)]
#[ORM\Table(name: 'video_progress')]
#[ORM\UniqueConstraint(name: 'unique_video_student', columns: ['video_id', 'student_id'])]
#[ORM\HasLifecycleCallbacks]
class VideoProgress
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: Video::class, inversedBy: 'progress')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Video $video = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $student = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $lastWatchedAt = 0; // seconds

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $totalWatched = 0; // seconds

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $percentageWatched = '0.00';

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $completed = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $resumableAt = 0; // seconds - where to resume from

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
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

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getLastWatchedAt(): ?int
    {
        return $this->lastWatchedAt;
    }

    public function setLastWatchedAt(int $lastWatchedAt): static
    {
        $this->lastWatchedAt = $lastWatchedAt;

        return $this;
    }

    public function getTotalWatched(): ?int
    {
        return $this->totalWatched;
    }

    public function setTotalWatched(int $totalWatched): static
    {
        $this->totalWatched = $totalWatched;

        return $this;
    }

    public function getPercentageWatched(): ?float
    {
        return $this->percentageWatched;
    }

    public function setPercentageWatched(float $percentageWatched): static
    {
        $this->percentageWatched = $percentageWatched;

        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getResumableAt(): ?int
    {
        return $this->resumableAt;
    }

    public function setResumableAt(int $resumableAt): static
    {
        $this->resumableAt = $resumableAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }
}
