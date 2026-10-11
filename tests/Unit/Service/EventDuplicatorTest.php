<?php

namespace App\Tests\Unit\Service;

use App\Entity\Events;
use App\Entity\Plans;
use App\Entity\Subscriptions;
use App\Service\EventDuplicator;
use PHPUnit\Framework\TestCase;

final class EventDuplicatorTest extends TestCase
{
    public function testCopiesSettingsAndShiftsSlotsToTheNewDate(): void
    {
        $source = $this->kermesse();

        $copy = (new EventDuplicator())->duplicate($source, 'Kermesse 2027', new \DateTime('2027-06-26 09:00'));

        self::assertSame('Kermesse 2027', $copy->getName());
        self::assertSame('Cour de l\'école', $copy->getLocation());
        self::assertTrue($copy->isSchedulingAuto());
        self::assertFalse($copy->isPublished(), 'Une copie n\'est jamais publiée d\'office');
        self::assertFalse($copy->isArchived());

        $plans = $copy->getPlans();
        self::assertCount(2, $plans);
        // 27/06/2026 -> 26/06/2027 : +364 jours, mêmes heures
        self::assertSame('2027-06-26 09:00', $plans[0]->getStartDate()->format('Y-m-d H:i'));
        self::assertSame('2027-06-26 11:00', $plans[0]->getEndDate()->format('Y-m-d H:i'));
        self::assertSame('2027-06-27 14:00', $plans[1]->getStartDate()->format('Y-m-d H:i'), 'Créneau du lendemain');
        self::assertSame('Buvette', $plans[0]->getActivityName());
        self::assertSame(3, $plans[0]->getNbPers());
        self::assertSame($copy, $plans[0]->getEvent());
    }

    public function testNeverCopiesVolunteersAndLeavesTheSourceUntouched(): void
    {
        $source = $this->kermesse();
        $source->getPlans()[0]->addSubscription(new Subscriptions());

        $copy = (new EventDuplicator())->duplicate($source, 'Copie', new \DateTime('2027-06-26'));

        self::assertCount(0, $copy->getSubscriptions());
        foreach ($copy->getPlans() as $plan) {
            self::assertCount(0, $plan->getSubscriptions());
        }
        self::assertSame('2026-06-27 09:00', $source->getPlans()[0]->getStartDate()->format('Y-m-d H:i'));
        self::assertCount(1, $source->getPlans()[0]->getSubscriptions());
    }

    private function kermesse(): Events
    {
        $event = (new Events())
            ->setName('Kermesse 2026')
            ->setStartAt(new \DateTime('2026-06-27 09:00'))
            ->setLocation('Cour de l\'école')
            ->setSchedulingAuto(true)
            ->setPublished(true);

        $event->addPlan($this->plan('Buvette', 3, '2026-06-27 09:00', '2026-06-27 11:00'));
        $event->addPlan($this->plan('Stand jeux', 2, '2026-06-28 14:00', '2026-06-28 16:00'));

        return $event;
    }

    private function plan(string $activity, int $nbPers, string $start, string $end): Plans
    {
        return (new Plans())
            ->setActivityName($activity)
            ->setNbPers($nbPers)
            ->setStartDate(new \DateTime($start))
            ->setEndDate(new \DateTime($end));
    }
}
