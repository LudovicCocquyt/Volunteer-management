<?php

namespace App\Controller\Api;

use App\Dto\PlanInput;
use App\Dto\VolunteerAssignmentInput;
use App\Http\ApiError;
use App\Repository\{PlansRepository, EventsRepository, SubscriptionsRepository};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ManageVolunteerSercive;
use App\Entity\Plans;
use DateTime;

#[Route('/api')]
final class ApiPlansController extends AbstractController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('/plans/by_event/{id}', name: 'api_plans_by_event', methods: ['GET'])]
    public function byEvent(int $id, PlansRepository $plansRepository): JsonResponse
    {
        $plans = $plansRepository->findBy(['event' => $id]);
        if (empty($plans)) {
            return new JsonResponse([], JsonResponse::HTTP_OK);
        }

        $plans = array_map(function(Plans $plan) {
            $need            = count($plan->getSubscriptions()) . "/" . $plan->getNbPers();
            $backgroundColor = count($plan->getSubscriptions()) >= $plan->getNbPers() ? 'bg-gray-400 border-gray-400 hover:bg-gray-800' :'bg-green-400 border-green-400 hover:bg-green-800';

            $people = ""; // Get the list of people who have subscribed to the plan for export pdf
            foreach ($plan->getSubscriptions() as $subscription) {
                $people = $people . ucfirst($subscription->getFirstname()) . ' ' . ucfirst(substr($subscription->getLastname(), 0, 1)) . "\n";
            }

            return [
                'id'           => $plan->getId(),
                'startDate'    => $plan->getStartDate()->format('Y-m-d\TH:i:s'),     // 2024-09-25T09:00:00
                'endDate'      => $plan->getEndDate()->format('Y-m-d\TH:i:s'),
                'activityName' => $plan->getActivityName(),
                "title"        => $need,
                "classNames"   => $backgroundColor,
                "people"       => strlen($people) > 0 ? "\n" . $people . "\n": "",
                "information"  => $plan->getInformation()
            ];
        }, $plans);

        $activities = [];
        foreach ($plans as $plan) {
            $activityName = (string) $plan['activityName'];
            if (!isset($activities[$activityName])) {
                $activities[$activityName] = [];
            }
            array_push($activities[$activityName], $plan);
        }

        return new JsonResponse($activities, JsonResponse::HTTP_OK);
    }

    /**
     * Version publique (formulaire d'inscription) : uniquement les événements publiés
     * et non archivés, créneaux vides exclus, noms des bénévoles seulement si
     * l'événement l'autorise, et au format "Prénom N.".
     */
    #[Route('/public/plans/our_needs_by_event/{id}', name: 'api_our_needs_plans_by_event', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function ourNeedsByEvent(int $id, EventsRepository $eventsRepo, PlansRepository $plansRepository, SubscriptionsRepository $subRepo): JsonResponse
    {
        $event = $eventsRepo->find($id);
        if (!$event || !$event->isPublished() || $event->isArchived()) {
            return new JsonResponse([], JsonResponse::HTTP_NOT_FOUND);
        }

        $peopleFormat = $event->isDisplayPeopleName() ? 'short' : 'none';

        return new JsonResponse($this->buildNeeds($id, false, $peopleFormat, $plansRepository, $subRepo, true), JsonResponse::HTTP_OK);
    }

    /**
     * Version back-office (planning, export PDF, édition d'inscription) : tous les
     * événements, noms complets (ROLE_USER via access_control).
     */
    #[Route('/plans/our_needs_by_event/{id}', name: 'api_our_needs_plans_by_event_private', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function ourNeedsByEventPrivate(Request $request, int $id, PlansRepository $plansRepository, SubscriptionsRepository $subRepo): JsonResponse
    {
        $includeEmpty = $request->query->getBoolean('includeEmpty');

        return new JsonResponse($this->buildNeeds($id, $includeEmpty, 'full', $plansRepository, $subRepo), JsonResponse::HTTP_OK);
    }

    /**
     * @param string $peopleFormat 'full' (Prénom Nom), 'short' (Prénom N.) ou 'none'
     * @param bool   $publicView   true : masque aussi les créneaux commencés/passés, et
     *                             compte les bénévoles déjà affectés pour savoir si un créneau est complet
     */
    private function buildNeeds(int $eventId, bool $includeEmpty, string $peopleFormat, PlansRepository $plansRepository, SubscriptionsRepository $subRepo, bool $publicView = false): array
    {
        $format = 'Y-m-d\TH:i:s';
        $plans  = $plansRepository->findBy(['event' => $eventId]);
        if (empty($plans)) {
            return [];
        }

        // Les créneaux sont saisis en heure de Paris : comparaison en heure locale
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s');

        // Nombre de bénévoles disponibles par créneau
        $volunteer_available = [];
        foreach ($subRepo->findBy(['event' => $eventId]) as $subscription) {
            foreach ($subscription->getAvailabilities() ?? [] as $s) {
                $key = $s['startDate'] . '/' . $s['endDate'];
                $volunteer_available[$key] = ($volunteer_available[$key] ?? 0) + 1;
            }
        }

        $our_needs = [];
        foreach ($plans as $plan) {
            if ($publicView && $plan->getStartDate()->format('Y-m-d H:i:s') <= $now) {
                continue; // créneau commencé ou passé
            }

            $startDate = $plan->getStartDate()->format($format);
            $endDate   = $plan->getEndDate()->format($format);

            // Plusieurs activités au même horaire : un seul créneau public, besoins cumulés
            foreach ($our_needs as &$need) {
                if ($need['startDate'] === $startDate && $need['endDate'] === $endDate) {
                    $need['nbPers']   += $plan->getNbPers();
                    $need['assigned'] += $plan->getSubscriptions()->count();
                    continue 2;
                }
            }
            unset($need);

            if ($includeEmpty || $plan->getNbPers() > 0) {
                $names = [];
                if ('none' !== $peopleFormat) {
                    foreach ($plan->getSubscriptions() as $s) {
                        $names[] = 'short' === $peopleFormat
                            ? ucfirst((string) $s->getFirstname()) . ' ' . mb_strtoupper(mb_substr((string) $s->getLastname(), 0, 1)) . '.'
                            : $s->getFirstname() . ' ' . $s->getLastname();
                    }
                }

                $our_needs[] = [
                    'startDate'   => $startDate,
                    'endDate'     => $endDate,
                    'nbPers'      => $plan->getNbPers(),
                    'assigned'    => $plan->getSubscriptions()->count(),
                    'available'   => true,
                    'information' => $plan->getInformation(),
                    'people'      => implode(",\n", $names),
                ];
            }
        }

        // Retire les créneaux complets
        foreach ($our_needs as $key => $o) {
            $taken = $volunteer_available[$o['startDate'] . '/' . $o['endDate']] ?? 0;
            if ($publicView) {
                $taken = max($taken, $o['assigned']);
            }
            if ($taken >= $o['nbPers']) {
                unset($our_needs[$key]);
            }
        }

        usort($our_needs, fn ($a, $b) => strcmp($a['startDate'], $b['startDate']));

        return array_map(function (array $need) {
            unset($need['assigned']);

            return $need;
        }, $our_needs);
    }

    #[Route('/plan/new', name: 'api_plan_new', methods: ['POST'])]
    public function new(
        #[MapRequestPayload(validationGroups: ['Default', 'create'])] PlanInput $input,
        EntityManagerInterface $entityManager,
        EventsRepository $eventsRepo,
        ValidatorInterface $validator,
    ): JsonResponse {
        $event = $eventsRepo->find((int) $input->eventId);
        if (!$event) {
            return ApiError::response('Événement introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }
        if ($event->isArchived()) {
            return $this->archivedResponse();
        }

        [$start, $end] = $this->slotDates($input);
        if (null === $start) {
            return $this->invalidSlotResponse();
        }

        $plan = (new Plans())
            ->setEvent($event)
            ->setActivityName($input->resourceId) // le nom de l'activité est l'identifiant de la ligne du calendrier
            ->setNbPers($input->nbPers())
            ->setStartDate($start)
            ->setEndDate($end)
            ->setInformation($input->information ?? '');

        $violations = $validator->validate($plan);
        if (count($violations) > 0) {
            return ApiError::fromViolations($violations);
        }

        $entityManager->persist($plan);
        $entityManager->flush();

        $jsonPlan = $this->serializer->serialize($plan, 'json', [
            'ignored_attributes' => ['event'],
            'groups'             => ['plan:read', 'subscription:read'],
            'enable_max_depth'   => true,
        ]);

        return new JsonResponse(['status' => 'Plan created!', 'plan' => json_decode($jsonPlan)], JsonResponse::HTTP_OK);
    }

    #[Route('/plan/edit/{id}', name: 'api_plan_edit', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function edit(
        Plans $plan,
        #[MapRequestPayload] PlanInput $input,
        EntityManagerInterface $entityManager,
        PlansRepository $plansRepo,
        ValidatorInterface $validator,
    ): JsonResponse {
        if ($plan->getEvent()?->isArchived()) {
            return $this->archivedResponse();
        }

        [$start, $end] = $this->slotDates($input);
        if (null === $start) {
            return $this->invalidSlotResponse();
        }

        $status = '[Plans updated]';
        $plan
            ->setStartDate($start)
            ->setEndDate($end)
            ->setNbPers($input->nbPers())
            ->setInformation($input->information ?? '');

        // Renommage d'une activité : tous ses créneaux sont renommés, mais
        // uniquement dans cet événement (les autres événements et les archives ne bougent pas)
        if ($plan->getActivityName() !== $input->resourceId) {
            $plans = $plansRepo->findByActivityName($plan->getActivityName(), $plan->getEvent());
            $this->changeActivityNameOfManyPlans($plans, $input->resourceId, $entityManager);
            $status = '[Plans updated] All plans of this activity in this event have been renamed.';
        }

        $violations = $validator->validate($plan);
        if (count($violations) > 0) {
            return ApiError::fromViolations($violations);
        }

        $entityManager->flush();

        $jsonPlan = $this->serializer->serialize($plan, 'json', [
            'ignored_attributes' => ['event', 'subscriptions'],
            'groups'             => ['plan:read', 'subscription:read'],
            'enable_max_depth'   => true,
        ]);

        return new JsonResponse(['status' => $status, 'plan' => json_decode($jsonPlan)], JsonResponse::HTTP_OK);
    }

    #[Route('/plan/volunteer', name: 'api_plan_volunteer', methods: ['PUT'])]
    public function volunteer(
        #[MapRequestPayload] VolunteerAssignmentInput $input,
        PlansRepository $plansRepository,
        SubscriptionsRepository $subRepo,
        ManageVolunteerSercive $manageVolunteer,
    ): JsonResponse {
        $plan         = $plansRepository->find($input->planId());
        $subscription = $subRepo->find($input->subscriptionId());
        if (!$plan || !$subscription) {
            return ApiError::response('Créneau ou bénévole introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }
        if ($plan->getEvent()?->isArchived()) {
            return $this->archivedResponse();
        }
        if ($subscription->getEvent() !== $plan->getEvent()) {
            return ApiError::response("Ce bénévole n'est pas inscrit à cet événement.", JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $input->assign
            ? $manageVolunteer->assign($plan, $subscription)
            : $manageVolunteer->remove($plan, $subscription);

        return new JsonResponse(['status' => 'Volunteer managed'], JsonResponse::HTTP_OK);
    }

    #[Route('/plan/get_subscriptions/{id}', name: 'api_plan_get_subscriptions', methods: ['GET'])]
    public function get_subscriptions(Plans $plan): JsonResponse
    {
        $jsonPlan = $this->serializer->serialize($plan->getSubscriptions(), 'json', ['ignored_attributes' => ['event', 'subscriptions']]);

        return new JsonResponse(
            [
                'status' => 'Get subscriptions',
                'plan'   => json_decode($jsonPlan)
            ], JsonResponse::HTTP_OK
        );
    }

    #[Route('/plan/remove/{id}', name: 'api_plans_delete', methods: ['DELETE'])]
    public function delete(Request $request, Plans $plan, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($plan->getEvent()?->isArchived()) {
            return $this->archivedResponse();
        }

        $entityManager->remove($plan);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Deleted!'], JsonResponse::HTTP_OK);
    }

    /**
     * Construit le début et la fin d'un créneau à partir du jour et des heures saisis.
     * Heure locale de Paris (heure d'été comme d'hiver), sans décalage codé en dur.
     *
     * @return array{0: ?DateTime, 1: ?DateTime} [null, null] si la saisie est invalide
     */
    private function slotDates(PlanInput $input): array
    {
        $timezone = new \DateTimeZone('Europe/Paris');
        $date     = substr((string) $input->start, 0, 10);
        $start    = DateTime::createFromFormat('!Y-m-d H:i', $date . ' ' . $input->startTime, $timezone);
        $end      = DateTime::createFromFormat('!Y-m-d H:i', $date . ' ' . $input->endTime, $timezone);

        $errors = DateTime::getLastErrors();
        if (!$start || !$end || ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $end <= $start) {
            return [null, null];
        }

        return [$start, $end];
    }

    private function invalidSlotResponse(): JsonResponse
    {
        return ApiError::response("Horaires invalides : l'heure de fin doit être après l'heure de début.", JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function archivedResponse(): JsonResponse
    {
        return ApiError::response("Événement archivé : modification impossible. Désarchivez-le d'abord.", JsonResponse::HTTP_FORBIDDEN);
    }

    private function changeActivityNameOfManyPlans(array $plans, string $newActivityName, $entityManager): void
    {
        foreach ($plans as $plan) {
            $plan->setActivityName($newActivityName);
            $entityManager->persist($plan);
        }
    }
}
