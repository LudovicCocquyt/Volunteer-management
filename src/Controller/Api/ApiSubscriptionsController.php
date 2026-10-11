<?php

namespace App\Controller\Api;

use App\Dto\SlotSearchInput;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

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
    public function ByEvent(#[MapRequestPayload] SlotSearchInput $input, SubscriptionsRepository $subsRepo): JsonResponse
    {
        $start = $input->startDate()->format('Y-m-d H:i:s');

        // Bénévoles qui ont déclaré cet horaire et n'y sont pas encore affectés
        $volunteers_available = [];
        foreach ($subsRepo->findBy(['event' => (int) $input->eventId]) as $volunteer) {
            foreach ($volunteer->getAvailabilitySlots() as $slot) {
                if (!$slot->isAssigned() && $slot->getStartDate()->format('Y-m-d H:i:s') === $start) {
                    $volunteers_available[] = $volunteer;
                    break;
                }
            }
        }

        // Évite les boucles pendant la sérialisation
        $json = $this->serializer->serialize($volunteers_available, 'json', ['ignored_attributes' => ['event', 'plans']]);

        return new JsonResponse([json_decode($json)], JsonResponse::HTTP_OK);
    }
}
