<?php

namespace App\Service;

use App\Entity\{Plans, Subscriptions};
use App\Repository\PlansRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;

class ManageVolunteerSercive
{
    private $entityManager;

    private $plansRepository;

    public function __construct(EntityManagerInterface $entityManager, PlansRepository $plansRepository)
    {
        $this->entityManager   = $entityManager;
        $this->plansRepository = $plansRepository;
    }

    public function assign(Plans $plan, Subscriptions $subscription): Subscriptions
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


    public function assignAuto(Subscriptions $subscription): bool
    {
        try {
            $availabilities = $subscription->getAvailabilities();
            foreach ($availabilities as $availability) {
                $plan   = $this->plansRepository->findByOneResultByTimes($availability["startDate"], $availability["endDate"], $subscription->getEvent());
                $result = $this->assign($plan, $subscription);
                $plan->addSubscription($result);

                $this->entityManager->persist($plan);
                $this->entityManager->flush();
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }

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