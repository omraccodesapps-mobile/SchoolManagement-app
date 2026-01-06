<?php

namespace App\Repository;

use App\Entity\Video;
use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Video>
 */
class VideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Video::class);
    }

    public function findByCourse(Course $course, string $status = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->where('v.course = :course')
            ->setParameter('course', $course)
            ->orderBy('v.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('v.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function findReadyVideos(Course $course): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.course = :course')
            ->andWhere('v.status = :status')
            ->setParameter('course', $course)
            ->setParameter('status', 'READY')
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.status = :status')
            ->setParameter('status', $status)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findProcessingVideos(): array
    {
        return $this->findByStatus('PROCESSING');
    }

    public function searchByTitle(string $query, Course $course = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->where('v.title LIKE :query OR v.description LIKE :query')
            ->setParameter('query', '%' . $query . '%');

        if ($course) {
            $qb->andWhere('v.course = :course')
                ->setParameter('course', $course);
        }

        return $qb->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
