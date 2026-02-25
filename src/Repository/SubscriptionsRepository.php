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

    public function findByEvent($id): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.event', 'e')
            ->where('e.archived != true')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->orderBy('e.id', 'ASC')
            ->addOrderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllByEvents($only_future): array
    {
        $subscriptions = $this->createQueryBuilder('s')
            ->join('s.event', 'e')
            ->where('e.archived != true') // If not archived
            ->orderBy('e.id', 'ASC') // Order by event.id
            ->addOrderBy('s.id', 'ASC') // And Order by subscription.id
            ->getQuery()
            ->getResult();

        $result = [];

        // Retire les dates passé
        if ($only_future) {
            $now = new \DateTimeImmutable();
            $subscriptions = array_filter($subscriptions, function ($subscription) use ($now) {
                foreach ($subscription->getAvailabilities() as $availability) {
                    if (new \DateTimeImmutable($availability['startDate']) > $now) {
                        return true; // garder cette subscription
                    }
                }
                return false; // aucune date future → on retire
            });
        }

        // Construction du tableau de données
        foreach ($subscriptions as $subscription) {

            $event   = $subscription->getEvent();
            $eventId = $event->getId();

            if (!isset($result[$eventId])) {
                $result[$eventId] = [];
            }

            $result[$eventId][] = $subscription;
        }

        return $result;
    }
}