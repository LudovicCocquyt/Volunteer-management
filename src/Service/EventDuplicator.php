<?php

namespace App\Service;

use App\Entity\Events;
use App\Entity\Plans;

/**
 * Duplique un événement (par exemple la kermesse de l'an dernier) :
 * réglages, pièces jointes et créneaux, décalés à la nouvelle date.
 * Les inscriptions et les affectations ne sont jamais copiées.
 */
final class EventDuplicator
{
    public function duplicate(Events $source, string $name, \DateTimeInterface $startAt): Events
    {
        $copy = (new Events())
            ->setName($name)
            ->setStartAt(\DateTime::createFromInterface($startAt))
            ->setDisplayStartAt((bool) $source->isDisplayStartAt())
            ->setLocation($source->getLocation())
            ->setDisplayLocation((bool) $source->isDisplayLocation())
            ->setDescription($source->getDescription())
            ->setSchedulingAuto((bool) $source->isSchedulingAuto())
            ->setDisplayPeopleName((bool) $source->isDisplayPeopleName())
            ->setDisplayCommentForSubscription((bool) $source->isDisplayCommentForSubscription())
            ->setStartCalendar($source->getStartCalendar() ? \DateTime::createFromInterface($source->getStartCalendar()) : null)
            ->setEndCalendar($source->getEndCalendar() ? \DateTime::createFromInterface($source->getEndCalendar()) : null)
            ->setManagerNotification($source->getManagerNotification())
            ->setMessageEmail($source->getMessageEmail())
            // Une copie n'est jamais visible tant qu'elle n'a pas été relue
            ->setPublished(false)
            ->setArchived(false);

        foreach ($source->getAttachments() as $attachment) {
            $copy->getAttachments()->add($attachment);
        }

        // Décalage en jours entiers : les heures des créneaux restent identiques
        $days = $this->dayOffset($source->getStartAt(), $startAt);

        foreach ($source->getPlans() as $plan) {
            $copy->addPlan(
                (new Plans())
                    ->setActivityName($plan->getActivityName())
                    ->setNbPers($plan->getNbPers())
                    ->setInformation($plan->getInformation())
                    ->setStartDate($this->shift($plan->getStartDate(), $days))
                    ->setEndDate($this->shift($plan->getEndDate(), $days))
            );
        }

        return $copy;
    }

    private function dayOffset(?\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        if (null === $from) {
            return 0;
        }

        $fromDay = new \DateTimeImmutable($from->format('Y-m-d'));
        $toDay   = new \DateTimeImmutable($to->format('Y-m-d'));

        return (int) $fromDay->diff($toDay)->format('%r%a');
    }

    private function shift(\DateTimeInterface $date, int $days): \DateTime
    {
        return \DateTime::createFromInterface($date)->modify(sprintf('%+d days', $days));
    }
}
