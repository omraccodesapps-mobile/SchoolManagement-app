<?php

namespace App\Entity;

use App\Repository\LiveChatMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: LiveChatMessageRepository::class)]
#[ORM\Table(name: 'live_chat_message')]
class LiveChatMessage
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: LiveSession::class, inversedBy: 'chatMessages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?LiveSession $session = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $isAnswer = false; // Teacher marking as answer

    #[ORM\PrePersist]
    public function setSentAtValue(): void
    {
        if ($this->sentAt === null) {
            $this->sentAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getSession(): ?LiveSession
    {
        return $this->session;
    }

    public function setSession(?LiveSession $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function isAnswer(): ?bool
    {
        return $this->isAnswer;
    }

    public function setIsAnswer(bool $isAnswer): static
    {
        $this->isAnswer = $isAnswer;

        return $this;
    }
}
