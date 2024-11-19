<?php

namespace App\Service;

use App\Entity\{Plans, Subscriptions};
use Doctrine\ORM\EntityManagerInterface;

class ManageVolunteerSercive
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function Assign(Plans $plan, Subscriptions $subscription): Subscriptions
    {
        // Update the availability of the subscription
        $availabilities = $subscription->getAvailabilities();
        foreach ($availabilities as $key => $availability) {
            $start = new \DateTime($availability["startDate"]);
            $end   = new \DateTime($availability["endDate"]);
            if ($start->getTimestamp() == $plan->getStartDate()->getTimestamp() && $end == $plan->getEndDate()) {
                $availabilities[$key]["available"] = false;
            }
        }
        $subscription->setAvailabilities($availabilities);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $subscription;
    }

    public function remove($plan, $subscription): Subscriptions
    {
        // Update the availability of the subscription
        $availabilities = $subscription->getAvailabilities();
        foreach ($availabilities as $key => $availability) {
            $start = new \DateTime($availability["startDate"]);
            $end   = new \DateTime($availability["endDate"]);
            if ($start->getTimestamp() == $plan->getStartDate()->getTimestamp() && $end == $plan->getEndDate()) {
                $availabilities[$key]["available"] = true;
            }
        }
        $subscription->setAvailabilities($availabilities);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $subscription;
    }
}