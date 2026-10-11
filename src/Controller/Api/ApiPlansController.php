<?php

namespace App\Controller\Api;

use App\Repository\{PlansRepository, EventsRepository, SubscriptionsRepository};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function new(Request $request, EntityManagerInterface $entityManager, EventsRepository $eventsRepo): JsonResponse
    {
        try {
            $params   = json_decode($request->getContent(), true);
            if (empty($params['nbPers'])) {
                $params['nbPers'] = 0;
            }
            $event = $eventsRepo->find($params['eventId']);
            if (empty($params['resourceId']) || empty($event)) {
                return new JsonResponse(['status' => 'Error', 'message' => 'Params not found'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $date = substr($params['start'], 0, 10);
            $start = DateTime::createFromFormat("Y-m-d\TH:i:sP", $date . "T" . $params['startTime'] . ":00+02:00");
            $end   = DateTime::createFromFormat("Y-m-d\TH:i:sP", $date . "T" . $params['endTime'] . ":00+02:00");

            $plan = new Plans();
            $plan->setEvent($event);
            $plan->setActivityName(strval($params['resourceId'])); //Activity name is the resourceId
            $plan->setNbPers(intval($params['nbPers']));
            $plan->setStartDate($start);
            $plan->setEndDate($end);
            $plan->setInformation(
                array_key_exists('information', $params) ? strval($params['information']) : ""
            );

            $entityManager->persist($plan);
            $entityManager->flush();

            $jsonPlan = $this->serializer->serialize($plan, 'json',
                ['ignored_attributes' => ['event'],
                'groups' => ['plan:read', 'subscription:read'],
                'enable_max_depth' => true]
            );

            return new JsonResponse(
                [
                    'status' => 'Plan created!',
                    'plan'   => json_decode($jsonPlan)
                ], JsonResponse::HTTP_OK
            );
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'Error', "message" => $e->getmessage()], 400);
        }
    }

    #[Route('/plan/edit/{id}', name: 'api_plan_edit', methods: ['PUT'])]
    public function edit(Plans $plan, Request $request, EntityManagerInterface $entityManager, PlansRepository $plansRepo): JsonResponse
    {
        try {
            $status = '[Plans updated]';
            $params = json_decode($request->getContent(), true);
            $date   = $params['start'];
            $start  = DateTime::createFromFormat("Y-m-d\TH:i:sP", $date . "T" . $params['startTime'] . ":00+02:00");
            $end    = DateTime::createFromFormat("Y-m-d\TH:i:sP", $date . "T" . $params['endTime'] . ":00+02:00");

            $plan->setStartDate($start);
            $plan->setEndDate($end);
            $plan->setNbPers(intval($params['nbPers']));
            $plan->setInformation(strval($params['information']));

            // If the activityName change, all plans with the same name must be modified.
            if ($plan->getActivityName() != $params['resourceId']) {
                $plans = $plansRepo->findByActivityName($plan->getActivityName());
                $this->changeActivityNameOfManyPlans($plans, $params['resourceId'], $entityManager);
                $status = '[Plans updated] With the same activity name, all plans have been updated.';
            }

            $entityManager->persist($plan);
            $entityManager->flush();

            $jsonPlan = $this->serializer->serialize(
                $plan,
                'json',
                [
                'ignored_attributes' => ['event', 'subscriptions'],
                'groups'             => ['plan:read', 'subscription:read'],
                'enable_max_depth'   => true
                ]
            );

            return new JsonResponse(
                [
                    'status' => $status,
                    'plan'   => json_decode($jsonPlan)
                ], JsonResponse::HTTP_OK
            );
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'Error', "message" => $e->getmessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/plan/volunteer', name: 'api_plan_volunteer', methods: ['PUT'])]
    public function volunteer(Request $request, PlansRepository $plansRepository, SubscriptionsRepository $subRepo, ManageVolunteerSercive $manageVolunteer, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $params       = json_decode($request->getContent(), true);
            $plan         = $plansRepository->find(intval($params['planId']));
            $subscription = $subRepo->find(intval($params["subscription"]["id"]));
            if (!array_key_exists('assign', $params)) {
                // Error data
                return new JsonResponse(['status' => 'Error', 'message' => 'assign key not found'], JsonResponse::HTTP_BAD_REQUEST);
            } elseif ($params['assign']) {
                // Add subscription to the plan
                $subscription = $manageVolunteer->assign($plan, $subscription);
                $plan->addSubscription($subscription);
            } else {
                // Remove subscription from the plan
                $subscription = $manageVolunteer->remove($plan, $subscription);
                $plan->removeSubscription($subscription);
            }
                $entityManager->persist($plan);
                $entityManager->flush();

            return new JsonResponse(['status' => 'Volunteer managed'], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'Error', "message" => $e->getmessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
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
        $entityManager->remove($plan);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Deleted!'], JsonResponse::HTTP_OK);
    }

    private function changeActivityNameOfManyPlans(array $plans, string $newActivityName, $entityManager): void
    {
        foreach ($plans as $plan) {
            $plan->setActivityName($newActivityName);
            $entityManager->persist($plan);
        }
    }
}
