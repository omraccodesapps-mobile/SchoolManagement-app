<?php

namespace App\Repository;

use App\Entity\LiveSession;
use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LiveSession>
 */
class LiveSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LiveSession::class);
    }

    public function findUpcomingSessions(int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.status = :status')
            ->andWhere('l.scheduledAt > :now')
            ->setParameter('status', 'SCHEDULED')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('l.scheduledAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findLiveSessions(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.status = :status')
            ->setParameter('status', 'LIVE')
            ->orderBy('l.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCourse(Course $course): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.course = :course')
            ->setParameter('course', $course)
            ->orderBy('l.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecordedSessions(Course $course = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.status = :status')
            ->andWhere('l.recordingUrl IS NOT NULL')
            ->setParameter('status', 'ENDED');

        if ($course) {
            $qb->andWhere('l.course = :course')
                ->setParameter('course', $course);
        }

        return $qb->orderBy('l.endedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
