<?php

namespace App\Controller\Api;

use App\Entity\Events;
use App\Repository\EventsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiEventsController extends AbstractController
{
    #[Route('/api/events', name: 'api_events', methods: ['GET'])]
    public function index(EventsRepository $eventsRepo): JsonResponse
    {
        $events = $eventsRepo->findAll();
        $events = array_map(function(Events $event) {
            return [
                'id'          => $event->getId(),
                'name'        => $event->getName(),
                'description' => $event->getDescription(),
                'date'        => $event->getStartAt()->format('H:i d-m-Y'),
                'location'    => $event->getLocation(),
                'published'   => $event->isPublished(),
                'archived'    => $event->isArchived(),
            ];
        }, $events);

        return new JsonResponse(array_reverse($events));
    }
}
