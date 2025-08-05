<?php

namespace App\Repository;

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
}
