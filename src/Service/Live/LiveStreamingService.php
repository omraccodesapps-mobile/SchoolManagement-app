<?php

namespace App\Service\Live;

use App\Entity\LiveSession;
use Doctrine\ORM\EntityManagerInterface;

class LiveStreamingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function startSession(LiveSession $session): void
    {
        if ($session->getStatus() !== 'SCHEDULED') {
            throw new \InvalidArgumentException('Session must be scheduled to start');
        }

        $session->setStatus('LIVE');
        $session->setStartedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    public function endSession(LiveSession $session): void
    {
        if ($session->getStatus() !== 'LIVE') {
            throw new \InvalidArgumentException('Session must be active to end');
        }

        $session->setStatus('ENDED');
        $session->setEndedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    public function cancelSession(LiveSession $session): void
    {
        if ($session->getStatus() !== 'SCHEDULED') {
            throw new \InvalidArgumentException('Only scheduled sessions can be cancelled');
        }

        $session->setStatus('ARCHIVED');
        $this->entityManager->flush();
    }

    public function getSessionStats(LiveSession $session): array
    {
        $attendances = $session->getAttendances();

        $totalParticipants = $attendances->count();

        return [
            'total_participants' => $totalParticipants,
            'current_participants' => $session->getAttendees(),
            'chat_messages_count' => $session->getChatMessages()->count(),
        ];
    }

    public function recordSession(LiveSession $session, string $recordingUrl): void
    {
        $session->setRecordingUrl($recordingUrl);
        $this->entityManager->flush();
    }

    public function getUpcomingSessions(int $limit = 10): array
    {
        return $this->entityManager->getRepository(LiveSession::class)->findBy(
            ['status' => 'SCHEDULED'],
            ['scheduledAt' => 'ASC'],
            $limit
        );
    }

    public function getActiveSessions(): array
    {
        return $this->entityManager->getRepository(LiveSession::class)->findBy(
            ['status' => 'LIVE']
        );
    }
}
