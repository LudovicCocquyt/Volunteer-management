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

    #[Route('/public/plans/our_needs_by_event/{id}', name: 'api_our_needs_plans_by_event', methods: ['GET'])]
    public function ourNeedsByEvent(int $id, PlansRepository $plansRepository, SubscriptionsRepository $subRepo): JsonResponse
    {
        setlocale(LC_TIME, 'fr_FR.UTF-8');
        $format = 'Y-m-d\TH:i:s';
        $plans = $plansRepository->findBy(['event' => $id]);
        if (empty($plans)) {
            return new JsonResponse([], JsonResponse::HTTP_OK);
        }

        $subscriptions       = $subRepo->findBy(['event' => $id]);
        $volunteer_available = []; // Array to store the number of volunteers available for each slot
        if (count($subscriptions) > 0) {
            foreach ($subscriptions as $subscription) {
                $slots_booked  = $subscription->getAvailabilities();
                foreach ($slots_booked as $s) {
                    if (array_key_exists($s['startDate'] ."/". $s['endDate'], $volunteer_available)) {
                        $volunteer_available[$s['startDate'] ."/". $s['endDate']] += 1;
                    } else {
                        $volunteer_available[$s['startDate'] ."/". $s['endDate']] = 1;
                    }
                }
            }
        };

        $our_needs = [];
        foreach ($plans as $plan) {
            $startDateExists = false;
            $endDateExists   = false;
            foreach ($our_needs as &$need) { // Use reference &$need to modify the existing array element
                if ($plan->getStartDate()->format($format) == $need['startDate']) {
                    $startDateExists = true;
                }
                if ($plan->getEndDate()->format($format) == $need['endDate']) {
                    $endDateExists = true;
                }
                if ($startDateExists && $endDateExists) {
                    $need['nbPers'] += $plan->getNbPers();
                    continue 2; // Skip to the next iteration of the outer loop
                }
            }

            // Add the plan to the our_needs array if getNbPers > 0
            if ($plan->getNbPers() > 0) {
                array_push($our_needs, [
                    'startDate' => $plan->getStartDate()->format($format),
                    'endDate'   => $plan->getEndDate()->format($format),
                    'nbPers'    => $plan->getNbPers(),
                    'available' => true
                ]);
            }

        }
        if (count($our_needs) > 0) {
            foreach ($our_needs as $key => $o) {
                $availabilityKey = $o['startDate'] . "/" . $o['endDate'];
                if (array_key_exists($availabilityKey, $volunteer_available) && $volunteer_available[$availabilityKey] >= $o['nbPers']) {
                    unset($our_needs[$key]);
                }
            }
        }

        usort($our_needs, function($a, $b) {
            return strtotime($a['startDate']) - strtotime($b['startDate']);
        });

        return new JsonResponse($our_needs, JsonResponse::HTTP_OK);
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
                return new JsonResponse(['status' => 'Error', 'message' => 'Assign key not found'], JsonResponse::HTTP_BAD_REQUEST);
            } elseif ($params['assign']) {
                // Add subscription to the plan
                $subscription = $manageVolunteer->Assign($plan, $subscription);
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
