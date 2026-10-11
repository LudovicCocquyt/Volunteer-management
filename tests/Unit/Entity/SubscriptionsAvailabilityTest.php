<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Events;
use App\Entity\Plans;
use App\Entity\Subscriptions;
use PHPUnit\Framework\TestCase;

final class SubscriptionsAvailabilityTest extends TestCase
{
    public function testAvailabilitiesAreCreatedFromFormData(): void
    {
        $subscription = new Subscriptions();
        $subscription->setAvailabilities([
            ['startDate' => '2026-12-05T09:00:00', 'endDate' => '2026-12-05T11:00:00', 'available' => true],
            ['startDate' => '2026-12-05T14:00:00', 'endDate' => '2026-12-05T16:00:00', 'available' => true],
        ]);

        self::assertCount(2, $subscription->getAvailabilitySlots());
        self::assertSame(
            ['startDate' => '2026-12-05T09:00:00', 'endDate' => '2026-12-05T11:00:00', 'available' => true, 'planId' => null],
            $subscription->getAvailabilities()[0]
        );
    }

    public function testInvalidEntriesAndDuplicatesAreIgnored(): void
    {
        $subscription = new Subscriptions();
        $subscription->setAvailabilities([
            ['startDate' => '2026-12-05T09:00:00', 'endDate' => '2026-12-05T11:00:00'],
            ['startDate' => '2026-12-05T09:00:00', 'endDate' => '2026-12-05T11:00:00'], // doublon
            ['startDate' => 'pas une date', 'endDate' => '2026-12-05T11:00:00'],
            ['endDate' => '2026-12-05T11:00:00'],
            'texte',
        ]);

        self::assertCount(1, $subscription->getAvailabilitySlots());
    }

    public function testSyncKeepsAssignmentOfKeptSlotAndDropsRemovedSlot(): void
    {
        $plan         = $this->plan('2026-12-05 09:00', '2026-12-05 11:00');
        $subscription = new Subscriptions();
        $subscription->setAvailabilities([
            ['startDate' => '2026-12-05T09:00:00', 'endDate' => '2026-12-05T11:00:00'],
            ['startDate' => '2026-12-05T14:00:00', 'endDate' => '2026-12-05T16:00:00'],
        ]);
        $plan->addSubscription($subscription);

        // Le formulaire renvoie le créneau affecté (avec available=true : ignoré) et en ajoute un autre
        $subscription->setAvailabilities([
            ['startDate' => '2026-12-05T09:00:00', 'endDate' => '2026-12-05T11:00:00', 'available' => true],
            ['startDate' => '2026-12-05T17:00:00', 'endDate' => '2026-12-05T18:00:00'],
        ]);

        $availabilities = $subscription->getAvailabilities();
        self::assertCount(2, $availabilities);
        self::assertFalse($availabilities[0]['available'], 'L\'affectation du créneau conservé doit rester');
        self::assertSame('2026-12-05T17:00:00', $availabilities[1]['startDate']);
        self::assertTrue($plan->getSubscriptions()->contains($subscription));
    }

    private function plan(string $start, string $end): Plans
    {
        return (new Plans())
            ->setEvent(new Events())
            ->setActivityName('Buvette')
            ->setNbPers(2)
            ->setStartDate(new \DateTime($start))
            ->setEndDate(new \DateTime($end));
    }
}
