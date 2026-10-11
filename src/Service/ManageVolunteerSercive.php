<?php

namespace App\Service;

use App\Entity\{Plans, Subscriptions};
use App\Repository\PlansRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ManageVolunteerSercive
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlansRepository $plansRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function assign(Plans $plan, Subscriptions $subscription): Subscriptions
    {
        $plan->addSubscription($subscription);
        $this->entityManager->flush();

        return $subscription;
    }

    /**
     * Affecte automatiquement le bénévole à un créneau pour chacune de ses disponibilités.
     *
     * Choix du créneau quand plusieurs activités ont le même horaire :
     *  1. un créneau qui n'est pas encore complet (le premier créé) ;
     *  2. sinon le premier créneau, même complet : le surbooking reste autorisé.
     *
     * Une disponibilité sans créneau correspondant est ignorée (pas d'erreur 500) :
     * les autres sont quand même affectées, et la méthode renvoie false pour que
     * l'organisateur soit prévenu par mail.
     *
     * @return bool true si toutes les disponibilités ont été affectées
     */
    public function assignAuto(Subscriptions $subscription): bool
    {
        $event       = $subscription->getEvent();
        $allAssigned = true;

        try {
            foreach ($subscription->getAvailabilitySlots() as $slot) {
                if ($slot->isAssigned()) {
                    continue; // déjà affecté sur cet horaire
                }

                $start = $slot->getStartDate();
                $end   = $slot->getEndDate();
                $plan  = $this->pickPlan($this->plansRepository->findBySlot($start, $end, $event), $subscription);

                if (null === $plan) {
                    $allAssigned = false;
                    $this->logger->warning('Auto-affectation : aucun créneau pour cette disponibilité', [
                        'event'        => $event?->getId(),
                        'subscription' => $subscription->getId(),
                        'start'        => $start->format('Y-m-d H:i'),
                        'end'          => $end->format('Y-m-d H:i'),
                    ]);
                    continue;
                }

                $plan->addSubscription($subscription);
            }

            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Auto-affectation interrompue', [
                'event'        => $event?->getId(),
                'subscription' => $subscription->getId(),
                'error'        => $e->getMessage(),
            ]);

            return false;
        }

        return $allAssigned;
    }

    /**
     * @param Plans[] $plans créneaux du même horaire, triés par ordre de création
     */
    private function pickPlan(array $plans, Subscriptions $subscription): ?Plans
    {
        $candidates = array_values(array_filter(
            $plans,
            fn (Plans $plan) => !$plan->getSubscriptions()->contains($subscription)
        ));

        if ([] === $candidates) {
            return null;
        }

        foreach ($candidates as $plan) {
            if ($plan->getSubscriptions()->count() < (int) $plan->getNbPers()) {
                return $plan; // place disponible
            }
        }

        return $candidates[0]; // tous complets : surbooking autorisé
    }

    public function remove(Plans $plan, Subscriptions $subscription): Subscriptions
    {
        $plan->removeSubscription($subscription);
        $this->entityManager->flush();

        return $subscription;
    }
}