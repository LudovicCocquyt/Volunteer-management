<?php

namespace App\Controller\Api;

use App\Entity\Plans;
use App\Repository\{PlansRepository, ActivitiesRepository, EventsRepository};
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

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
        // $plans = $plansRepository->findAll();
        $plans = array_map(function(Plans $plan) {
            return [
                'id'        => $plan->getId(),
                'startDate' => $plan->getStartDate()->format('Y-m-d\TH:i:s'), // 2024-09-25T09:00:00
                'endDate'   => $plan->getEndDate()->format('Y-m-d\TH:i:s'),
                'nbPers'    => $plan->getNbPers(),
                'activity'  => [
                    'id'   => $plan->getActivity()->getId(),
                    'name' => $plan->getActivity()->getName(),
                ],
            ];
        }, $plans);

        $activities   = [];
        $activityName = [];
        foreach ($plans as $plan) {
            $activityName = $plan['activity']['name'];
            if (!isset($activities[$activityName])) {
                $activities[$activityName] = [];
            }
            array_push($activities[$activityName], $plan);
        }
        return new JsonResponse(array_reverse($activities), JsonResponse::HTTP_OK);
    }

    #[Route('/plans/our_needs_by_event/{id}', name: 'api_our_needs_plans_by_event', methods: ['GET'])]
    public function ourNeedsByEvent(int $id, PlansRepository $plansRepository): JsonResponse
    {
        setlocale(LC_TIME, 'fr_FR.UTF-8');
        $format = 'Y-m-dTH:i:s+01';
        $plans = $plansRepository->findBy(['event' => $id]);
        if (empty($plans)) {
            return new JsonResponse([], JsonResponse::HTTP_OK);
        }

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

            if ($plan->getNbPers() > 0) {
                array_push($our_needs, [
                    'startDate' => $plan->getStartDate()->format($format),
                    'endDate'   => $plan->getEndDate()->format($format),
                    'nbPers'    => $plan->getNbPers()
                ]);
            }
        }

        usort($our_needs, function($a, $b) {
            return strtotime($a['startDate']) - strtotime($b['startDate']);
        });

        return new JsonResponse($our_needs, JsonResponse::HTTP_OK);
    }

    #[Route('/plan/new', name: 'api_plan_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivitiesRepository $activitiesRepo, EventsRepository $eventsRepo): JsonResponse
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new JsonResponse(['status' => 'Error'], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            $params   = json_decode($request->getContent(), true);
            if (empty($params['nbPers'])) {
                $params['nbPers'] = 0;
            }
            $activity = $activitiesRepo->findOneBy(['name' => $params['resourceId']]);
            $event    = $eventsRepo->find($params['eventId']);

            if (empty($activity) || empty($event)) {
                return new JsonResponse(['status' => 'Error', 'message' => 'Params not found'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $date = substr($params['start'], 0, 10);
            $start = DateTime::createFromFormat("Y-m-d\TH:i:sP", $date . "T" . $params['startTime'] . ":00+02:00");
            $end   = DateTime::createFromFormat("Y-m-d\TH:i:sP", $date . "T" . $params['endTime'] . ":00+02:00");

            $plan = new Plans();
            $plan->setEvent($event);
            $plan->setActivity($activity);
            $plan->setNbPers(intval($params['nbPers']));
            $plan->setStartDate($start);
            $plan->setEndDate($end);

            $entityManager->persist($plan);
            $entityManager->flush();

            $jsonPlan = $this->serializer->serialize($plan, 'json', ['ignored_attributes' => ['event']]);

            return new JsonResponse(
                [
                    'status' => 'activity created!',
                    'plan'   => json_decode($jsonPlan)
                ], JsonResponse::HTTP_OK
            );
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'Error', "message" => $e->getmessage()], 400);
        }
    }

    #[Route('/plan/edit/{id}', name: 'api_plan_edit', methods: ['PUT'])]
    public function edit(Plans $plan, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new JsonResponse(['status' => 'Error'], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            $params = json_decode($request->getContent(), true);
            $date  = $params['start'];
            $start = DateTime::createFromFormat("Y-m-d\TH:i:sP", $date . "T" . $params['startTime'] . ":00+02:00");
            $end   = DateTime::createFromFormat("Y-m-d\TH:i:sP", $date . "T" . $params['endTime'] . ":00+02:00");

            $plan->setStartDate($start);
            $plan->setEndDate($end);
            $plan->setNbPers(intval($params['nbPers']));
            $entityManager->persist($plan);
            $entityManager->flush();

            $jsonPlan = $this->serializer->serialize($plan, 'json', ['ignored_attributes' => ['event']]);

            return new JsonResponse(
                [
                    'status' => 'activity updated!',
                    'plan'   => json_decode($jsonPlan)
                ], JsonResponse::HTTP_OK
            );
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'Error', "message" => $e->getmessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/plan/remove/{id}', name: 'api_plans_delete', methods: ['DELETE'])]
    public function delete(Request $request, Plans $plan, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY') || !$plan instanceof Plans) {
            return new JsonResponse(['status' => 'Error'], JsonResponse::HTTP_FORBIDDEN);
        }

        $entityManager->remove($plan);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Deleted!'], JsonResponse::HTTP_OK);
    }

    
}
