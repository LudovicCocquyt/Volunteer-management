<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Events;
use App\Entity\Plans;
use App\Entity\Subscriptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class EntityValidationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testPlanEndMustBeAfterStart(): void
    {
        $plan = (new Plans())
            ->setEvent(new Events())
            ->setActivityName('Buvette')
            ->setNbPers(2)
            ->setStartDate(new \DateTime('2026-06-27 11:00'))
            ->setEndDate(new \DateTime('2026-06-27 09:00'));

        self::assertSame(['endDate'], $this->paths($plan));
    }

    public function testPlanRequiresAnActivityAndAReasonableCapacity(): void
    {
        $plan = (new Plans())
            ->setEvent(new Events())
            ->setActivityName('')
            ->setNbPers(5000)
            ->setStartDate(new \DateTime('2026-06-27 09:00'))
            ->setEndDate(new \DateTime('2026-06-27 11:00'));

        self::assertEqualsCanonicalizing(['activityName', 'nbPers'], $this->paths($plan));
    }

    public function testEventNotificationAddressesMustAllBeValid(): void
    {
        $event = (new Events())
            ->setName('Kermesse')
            ->setStartAt(new \DateTime('2026-06-27'))
            ->setManagerNotification('orga@example.fr, pas-une-adresse');

        self::assertSame(['managerNotification'], $this->paths($event));

        $event->setManagerNotification('orga@example.fr, tresorier@example.fr');
        self::assertSame([], $this->paths($event));
    }

    public function testSubscriptionContactDetails(): void
    {
        $subscription = (new Subscriptions())
            ->setFirstname('Jean')
            ->setLastname('Dupont')
            ->setEmail('pas-un-email')
            ->setPhone('appelle-moi')
            ->setChildName('Léa')
            ->setClass('CM2');

        self::assertEqualsCanonicalizing(['email', 'phone'], $this->paths($subscription));

        $subscription->setEmail('jean.dupont@example.fr')->setPhone('+33 6 12 34 56 78');
        self::assertSame([], $this->paths($subscription));
    }

    /** @return string[] */
    private function paths(object $entity): array
    {
        $paths = [];
        foreach ($this->validator->validate($entity) as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        return array_values(array_unique($paths));
    }
}
