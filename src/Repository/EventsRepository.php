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

    /**
     * Événements affichés sur la page publique : publiés, non archivés et non terminés.
     */
    public function findPublishedEvents(): array
    {
        // Les dates sont saisies en heure de Paris
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));

        $events = $this->createQueryBuilder('e')
            ->select('e.id, e.name, e.startAt, e.location, e.description, e.displayPeopleName, e.displayCommentForSubscription, e.displayStartAt, e.displayLocation')
            ->andWhere('e.published = :published')
            ->andWhere('e.archived = :archived')
            // Événement encore ouvert : au moins un créneau à venir,
            // ou pas encore de créneau et une date d'événement qui n'est pas passée
            ->andWhere('(EXISTS (SELECT p1.id FROM App\Entity\Plans p1 WHERE p1.event = e AND p1.startDate > :now)
                OR (NOT EXISTS (SELECT p2.id FROM App\Entity\Plans p2 WHERE p2.event = e) AND e.startAt >= :today))')
            ->setParameter('published', true)
            ->setParameter('archived', false)
            ->setParameter('now', $now->format('Y-m-d H:i:s'))
            ->setParameter('today', $now->format('Y-m-d 00:00:00'))
            ->orderBy('e.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        // Le lieu et la date masqués ne doivent pas apparaître dans le code source de la page publique
        return array_map(function (array $event) {
            if (!$event['displayLocation']) {
                $event['location'] = null;
            }
            if (!$event['displayStartAt']) {
                $event['startAt'] = null;
            }

            return $event;
        }, $events);
    }
}
