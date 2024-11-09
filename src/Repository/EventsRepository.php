<?php

namespace App\Repository;

use App\Entity\Events;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Events>
 */
class EventsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Events::class);
    }

    public function findPublishedAndNotArchivedEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.id, e.name, e.startAt, e.location, e.description')
            ->andWhere('e.published = :published')
            ->andWhere('e.archived = :archived')
            ->setParameter('published', true)
            ->setParameter('archived', false)
            ->orderBy('e.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
