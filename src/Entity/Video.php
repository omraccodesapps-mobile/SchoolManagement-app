<?php

namespace App\Entity;

use App\Repository\VideoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
#[ORM\Table(name: 'video')]
#[ORM\HasLifecycleCallbacks]
class Video
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'videos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Course $course = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploadedBy = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $type = 'ON_DEMAND'; // ON_DEMAND, LIVE, RECORDING

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $status = 'DRAFT'; // DRAFT, PROCESSING, READY, ARCHIVED, FAILED

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $videoUrl = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $thumbnailUrl = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $duration = null; // seconds

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $updatedAt = null;

    #[ORM\OneToMany(targetEntity: VideoVariant::class, mappedBy: 'video', cascade: ['all'], orphanRemoval: true)]
    private Collection $variants;

    #[ORM\OneToMany(targetEntity: VideoChapter::class, mappedBy: 'video', cascade: ['all'], orphanRemoval: true)]
    private Collection $chapters;

    #[ORM\OneToMany(targetEntity: VideoQuiz::class, mappedBy: 'video', cascade: ['all'], orphanRemoval: true)]
    private Collection $quizzes;

    #[ORM\OneToMany(targetEntity: VideoNote::class, mappedBy: 'video', cascade: ['all'], orphanRemoval: true)]
    private Collection $notes;

    #[ORM\OneToMany(targetEntity: VideoProgress::class, mappedBy: 'video', cascade: ['all'], orphanRemoval: true)]
    private Collection $progress;

    #[ORM\OneToOne(targetEntity: VideoTranscript::class, mappedBy: 'video', cascade: ['all'], orphanRemoval: true)]
    private ?VideoTranscript $transcript = null;

    public function __construct()
    {
        $this->variants = new ArrayCollection();
        $this->chapters = new ArrayCollection();
        $this->quizzes = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->progress = new ArrayCollection();
    }

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

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): static
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl(?string $thumbnailUrl): static
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

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

    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function addVariant(VideoVariant $variant): static
    {
        if (!$this->variants->contains($variant)) {
            $this->variants->add($variant);
            $variant->setVideo($this);
        }

        return $this;
    }

    public function removeVariant(VideoVariant $variant): static
    {
        if ($this->variants->removeElement($variant)) {
            if ($variant->getVideo() === $this) {
                $variant->setVideo(null);
            }
        }

        return $this;
    }

    public function getChapters(): Collection
    {
        return $this->chapters;
    }

    public function addChapter(VideoChapter $chapter): static
    {
        if (!$this->chapters->contains($chapter)) {
            $this->chapters->add($chapter);
            $chapter->setVideo($this);
        }

        return $this;
    }

    public function removeChapter(VideoChapter $chapter): static
    {
        if ($this->chapters->removeElement($chapter)) {
            if ($chapter->getVideo() === $this) {
                $chapter->setVideo(null);
            }
        }

        return $this;
    }

    public function getQuizzes(): Collection
    {
        return $this->quizzes;
    }

    public function addQuiz(VideoQuiz $quiz): static
    {
        if (!$this->quizzes->contains($quiz)) {
            $this->quizzes->add($quiz);
            $quiz->setVideo($this);
        }

        return $this;
    }

    public function removeQuiz(VideoQuiz $quiz): static
    {
        if ($this->quizzes->removeElement($quiz)) {
            if ($quiz->getVideo() === $this) {
                $quiz->setVideo(null);
            }
        }

        return $this;
    }

    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(VideoNote $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setVideo($this);
        }

        return $this;
    }

    public function removeNote(VideoNote $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getVideo() === $this) {
                $note->setVideo(null);
            }
        }

        return $this;
    }

    public function getProgress(): Collection
    {
        return $this->progress;
    }

    public function addProgress(VideoProgress $progress): static
    {
        if (!$this->progress->contains($progress)) {
            $this->progress->add($progress);
            $progress->setVideo($this);
        }

        return $this;
    }

    public function removeProgress(VideoProgress $progress): static
    {
        if ($this->progress->removeElement($progress)) {
            if ($progress->getVideo() === $this) {
                $progress->setVideo(null);
            }
        }

        return $this;
    }

    public function getTranscript(): ?VideoTranscript
    {
        return $this->transcript;
    }

    public function setTranscript(?VideoTranscript $transcript): static
    {
        if ($transcript === null && $this->transcript !== null) {
            $this->transcript->setVideo(null);
        }

        if ($transcript !== null && $transcript->getVideo() !== $this) {
            $transcript->setVideo($this);
        }

        $this->transcript = $transcript;

        return $this;
    }
}
