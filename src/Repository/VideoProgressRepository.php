<?php

namespace App\Repository;

use App\Entity\VideoProgress;
use App\Entity\Video;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VideoProgress>
 */
class VideoProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VideoProgress::class);
    }

    public function findOrCreate(Video $video, User $student): VideoProgress
    {
        $progress = $this->findOneBy([
            'video' => $video,
            'student' => $student
        ]);

        if (!$progress) {
            $progress = new VideoProgress();
            $progress->setVideo($video);
            $progress->setStudent($student);
        }

        return $progress;
    }

    public function findCompletedByStudent(User $student): array
    {
        return $this->createQueryBuilder('vp')
            ->where('vp.student = :student')
            ->andWhere('vp.completed = :completed')
            ->setParameter('student', $student)
            ->setParameter('completed', true)
            ->getQuery()
            ->getResult();
    }

    public function findInProgressByStudent(User $student): array
    {
        return $this->createQueryBuilder('vp')
            ->where('vp.student = :student')
            ->andWhere('vp.completed = :completed')
            ->setParameter('student', $student)
            ->setParameter('completed', false)
            ->orderBy('vp.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
