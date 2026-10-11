<?php

namespace App\Tests\Unit\Service;

use App\Entity\Events;
use App\Entity\Plans;
use App\Entity\Subscriptions;
use App\Repository\PlansRepository;
use App\Service\ManageVolunteerSercive;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AutoAssignTest extends TestCase
{
    private Events $event;

    protected function setUp(): void
    {
        $this->event = new Events();
    }

    public function testPrefersASlotWithRemainingCapacity(): void
    {
        $full = $this->plan(1);
        $full->addSubscription(new Subscriptions());
        $free = $this->plan(2);

        $subscription = $this->subscription();
        $result       = $this->service([$full, $free])->assignAuto($subscription);

        self::assertTrue($result);
        self::assertSame($free, $subscription->getAvailabilitySlots()->first()->getPlan());
    }

    public function testOverbooksTheFirstSlotWhenAllAreFull(): void
    {
        $first = $this->plan(1);
        $first->addSubscription(new Subscriptions());
        $second = $this->plan(1);
        $second->addSubscription(new Subscriptions());

        $subscription = $this->subscription();
        $result       = $this->service([$first, $second])->assignAuto($subscription);

        self::assertTrue($result, 'Le surbooking est autorisé');
        self::assertSame($first, $subscription->getAvailabilitySlots()->first()->getPlan());
        self::assertCount(2, $first->getSubscriptions());
    }

    public function testReturnsFalseWithoutErrorWhenNoSlotMatches(): void
    {
        $subscription = $this->subscription();
        $result       = $this->service([])->assignAuto($subscription);

        self::assertFalse($result);
        self::assertFalse($subscription->getAvailabilitySlots()->first()->isAssigned());
    }

    public function testAlreadyAssignedAvailabilityIsSkipped(): void
    {
        $plan         = $this->plan(2);
        $subscription = $this->subscription();
        $plan->addSubscription($subscription);

        $repository = $this->createMock(PlansRepository::class);
        $repository->expects(self::never())->method('findBySlot');

        $service = new ManageVolunteerSercive($this->createStub(EntityManagerInterface::class), $repository, new NullLogger());

        self::assertTrue($service->assignAuto($subscription));
    }

    private function service(array $plansForSlot): ManageVolunteerSercive
    {
        // Stubs (pas de vérification d'appel) : PHPUnit 12+ émet une notice pour un mock sans attente
        $repository = $this->createStub(PlansRepository::class);
        $repository->method('findBySlot')->willReturn($plansForSlot);

        return new ManageVolunteerSercive($this->createStub(EntityManagerInterface::class), $repository, new NullLogger());
    }

    private function subscription(): Subscriptions
    {
        $subscription = (new Subscriptions())->setEvent($this->event);
        $subscription->setAvailabilities([
            ['startDate' => '2026-12-05T09:00:00', 'endDate' => '2026-12-05T11:00:00'],
        ]);

        return $subscription;
    }

    private function plan(int $nbPers): Plans
    {
        return (new Plans())
            ->setEvent($this->event)
            ->setActivityName('Activité ' . $nbPers)
            ->setNbPers($nbPers)
            ->setStartDate(new \DateTime('2026-12-05 09:00'))
            ->setEndDate(new \DateTime('2026-12-05 11:00'));
    }
}
