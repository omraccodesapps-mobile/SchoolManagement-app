<?php

namespace App\Entity;

use App\Repository\LiveSessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: LiveSessionRepository::class)]
#[ORM\Table(name: 'live_session')]
#[ORM\HasLifecycleCallbacks]
class LiveSession
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Course $course = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $teacher = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $status = 'SCHEDULED'; // SCHEDULED, LIVE, ENDED, ARCHIVED

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private ?string $webrtcRoom = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $recordingUrl = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $attendees = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(targetEntity: LiveAttendance::class, mappedBy: 'session', cascade: ['all'], orphanRemoval: true)]
    private Collection $attendances;

    #[ORM\OneToMany(targetEntity: LiveChatMessage::class, mappedBy: 'session', cascade: ['all'], orphanRemoval: true)]
    private Collection $chatMessages;

    public function __construct()
    {
        $this->attendances = new ArrayCollection();
        $this->chatMessages = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        if ($this->webrtcRoom === null) {
            $this->webrtcRoom = 'room-' . uniqid() . '-' . bin2hex(random_bytes(4));
        }
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

    public function getTeacher(): ?User
    {
        return $this->teacher;
    }

    public function setTeacher(?User $teacher): static
    {
        $this->teacher = $teacher;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): static
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getWebrtcRoom(): ?string
    {
        return $this->webrtcRoom;
    }

    public function setWebrtcRoom(string $webrtcRoom): static
    {
        $this->webrtcRoom = $webrtcRoom;

        return $this;
    }

    public function getRecordingUrl(): ?string
    {
        return $this->recordingUrl;
    }

    public function setRecordingUrl(?string $recordingUrl): static
    {
        $this->recordingUrl = $recordingUrl;

        return $this;
    }

    public function getAttendees(): ?int
    {
        return $this->attendees;
    }

    public function setAttendees(int $attendees): static
    {
        $this->attendees = $attendees;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAttendances(): Collection
    {
        return $this->attendances;
    }

    public function addAttendance(LiveAttendance $attendance): static
    {
        if (!$this->attendances->contains($attendance)) {
            $this->attendances->add($attendance);
            $attendance->setSession($this);
        }

        return $this;
    }

    public function removeAttendance(LiveAttendance $attendance): static
    {
        if ($this->attendances->removeElement($attendance)) {
            if ($attendance->getSession() === $this) {
                $attendance->setSession(null);
            }
        }

        return $this;
    }

    public function getChatMessages(): Collection
    {
        return $this->chatMessages;
    }

    public function addChatMessage(LiveChatMessage $chatMessage): static
    {
        if (!$this->chatMessages->contains($chatMessage)) {
            $this->chatMessages->add($chatMessage);
            $chatMessage->setSession($this);
        }

        return $this;
    }

    public function removeChatMessage(LiveChatMessage $chatMessage): static
    {
        if ($this->chatMessages->removeElement($chatMessage)) {
            if ($chatMessage->getSession() === $this) {
                $chatMessage->setSession(null);
            }
        }

        return $this;
    }
}
