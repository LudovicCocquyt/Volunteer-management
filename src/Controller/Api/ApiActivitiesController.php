<?php

namespace App\Controller\Api;

use App\Entity\Activities;
use App\Repository\ActivitiesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ApiActivitiesController extends AbstractController
{
    #[Route('/api/activity/new', name: 'api_activity_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new JsonResponse(['status' => 'Error'], 403);
        }
        try {
            $params = json_decode($request->getContent(), true);

            $activity = new Activities();
            $activity->setName($params['name']);
            $activity->setDescription($params['description']);
            $activity->setCreatedAt(new \DateTime);
            $activity->setCreatedBy($this->getUser()->getId());

            $entityManager->persist($activity);
            $entityManager->flush();

            return new JsonResponse(['status' => 'activity created!'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'Error', "message" => $e->getmessage()], 400);
        }
    }

    #[Route('/api/activities', name: 'api_activities', methods: ['GET'])]
    public function index(ActivitiesRepository $activiesRepo): JsonResponse
    {
        $activities = $activiesRepo->findAll();
        $activities = array_map(function(Activities $activity) {
            return [
                'id'          => $activity->getId(),
                'name'        => $activity->getName(),
                'description' => $activity->getDescription(),
            ];
        }, $activities);

        return new JsonResponse(array_reverse($activities));
    }

    #[Route('/api/delete_activity/{id}', name: 'api_delete_activity', methods: ['DELETE'])]
    public function deleteActivity(Activities $activity, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new JsonResponse(['status' => 'Error'], 403);
        }
        try {
            $entityManager->remove($activity);
            $entityManager->flush();
            return new JsonResponse(['status' => 'Activity deleted!'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'Error', "message" => $e->getmessage()], 400);
        }
    }
}
