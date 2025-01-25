<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\SubscriptionsRepository;

#[Route('/api')]
final class ApiSubscriptionsController extends AbstractController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('/subscriptions', name: 'api_subscriptions', methods: ['POST'])]
    public function ByEvent(Request $request, SubscriptionsRepository $subsRepo): JsonResponse
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new JsonResponse(['status' => 'Error'], JsonResponse::HTTP_FORBIDDEN);
        }

        $params        = json_decode($request->getContent(), true);
        $subscriptions = $subsRepo->findBy(['event' => $params['eventId']]);

        $volunteers_available = [];
        foreach ($subscriptions as $volunteer) {
            $availabilities = $volunteer->getAvailabilities();
            foreach ($availabilities as $availability) {
                $a = new \DateTime($availability["startDate"]);
                $b = new \DateTime($params["start"]);
                if ($a->getTimestamp() === $b->getTimestamp() && $availability["available"]) {
                    array_push($volunteers_available, $volunteer);
                }
            }
        }

        // Avoids loops during serialisation
        // ['ignored_attributes' => ['event', 'plan']
        $json = $this->serializer->serialize($volunteers_available, 'json', ['ignored_attributes' => ['event', 'plans']]);

        return new JsonResponse([json_decode($json)], JsonResponse::HTTP_OK);
    }
}
