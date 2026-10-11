<?php

namespace App\Repository;

use App\Entity\Events;
use App\Entity\Plans;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plans>
 */
class PlansRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plans::class);
    }

    /**
     * @return Plans[] Returns an array of Plans objects
     */
    public function findAll(): array
    {
        $results = $this->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        $plansWithEventName = array_map(function($plan) {
            $plan->getEvent()->getName();
            return $plan;
        }, $results);

        return $plansWithEventName;
    }
    public function findByActivityName(string $name): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.activityName = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getResult();
    }

    /**
     * Créneaux d'un événement correspondant exactement à une disponibilité (même début, même fin).
     *
     * @return Plans[]
     */
    public function findBySlot(\DateTimeInterface $startDate, \DateTimeInterface $endDate, Events $event): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.event = :event')
            ->andWhere('p.startDate = :startDate')
            ->andWhere('p.endDate = :endDate')
            ->setParameter('event', $event)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
