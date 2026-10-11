<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Events;
use App\Entity\Plans;
use App\Entity\Subscriptions;
use PHPUnit\Framework\TestCase;

final class PlansAssignmentTest extends TestCase
{
    public function testAssigningCreatesTheMissingAvailability(): void
    {
        $plan         = $this->plan('Buvette');
        $subscription = new Subscriptions();

        $plan->addSubscription($subscription);

        self::assertCount(1, $subscription->getAvailabilitySlots());
        self::assertSame($plan, $subscription->getAvailabilitySlots()->first()->getPlan());
        self::assertCount(1, $plan->getSubscriptions());
        self::assertTrue($subscription->getPlans()->contains($plan));
    }

    public function testAssigningTwiceIsIdempotent(): void
    {
        $plan         = $this->plan('Buvette');
        $subscription = new Subscriptions();

        $plan->addSubscription($subscription);
        $plan->addSubscription($subscription);

        self::assertCount(1, $plan->getSubscriptions());
        self::assertCount(1, $subscription->getAvailabilitySlots());
    }

    public function testOneAssignmentPerTimeSlot(): void
    {
        $buvette      = $this->plan('Buvette');
        $jeux         = $this->plan('Stand jeux'); // même horaire
        $subscription = new Subscriptions();

        $buvette->addSubscription($subscription);
        $jeux->addSubscription($subscription);

        self::assertCount(0, $buvette->getSubscriptions(), 'Le bénévole a été déplacé');
        self::assertCount(1, $jeux->getSubscriptions());
        self::assertCount(1, $subscription->getAvailabilitySlots());
    }

    public function testRemovingKeepsTheAvailability(): void
    {
        $plan         = $this->plan('Buvette');
        $subscription = new Subscriptions();
        $plan->addSubscription($subscription);

        $plan->removeSubscription($subscription);

        self::assertCount(0, $plan->getSubscriptions());
        self::assertCount(1, $subscription->getAvailabilitySlots());
        self::assertTrue($subscription->getAvailabilities()[0]['available']);
    }

    private function plan(string $activity): Plans
    {
        return (new Plans())
            ->setEvent(new Events())
            ->setActivityName($activity)
            ->setNbPers(2)
            ->setStartDate(new \DateTime('2026-12-05 09:00'))
            ->setEndDate(new \DateTime('2026-12-05 11:00'));
    }
}
