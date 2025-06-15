<?php

namespace App\Controller\Api;

use App\Entity\Events;
use App\Repository\EventsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiEventsController extends AbstractController
{
    #[Route('/api/public/events', name: 'api_events', methods: ['GET'])]
    public function index(EventsRepository $eventsRepo): JsonResponse
    {
        $events = $eventsRepo->findBy([], ['published' => 'ASC', 'id' => 'ASC']);
        $events = array_map(function(Events $event) {
            return [
                'id'            => $event->getId(),
                'name'          => $event->getName(),
                'description'   => $event->getDescription(),
                'date'          => $event->getStartAt()->format('H:i d-m-Y'),
                'location'      => $event->getLocation(),
                'published'     => $event->isPublished(),
                'archived'      => $event->isArchived(),
                'plans'         => $event->getNbPersonsByEvent(),
                'subscriptions' => $event->reservedAvailability(),
            ];
        }, $events);

        return new JsonResponse(array_reverse($events));
    }
}
