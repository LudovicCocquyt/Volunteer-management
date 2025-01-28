<?php

namespace App\Repository;

use App\Entity\Subscriptions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscriptions>
 */
class SubscriptionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscriptions::class);
    }

    public function findAllByEvent(): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.event', 'e')
            ->where('e.archived != true') // If not archived
            ->orderBy('e.id', 'ASC') // Order by event.id
            ->addOrderBy('s.id', 'ASC') // And Order by subscription.id
            ->getQuery()
            ->getResult();
    }
}