<?php

namespace App\Repository;

use App\Entity\VideoTranscript;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VideoTranscript>
 */
class VideoTranscriptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VideoTranscript::class);
    }

    public function findReadyTranscripts(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', 'READY')
            ->getQuery()
            ->getResult();
    }
}
