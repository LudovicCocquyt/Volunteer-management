<?php

namespace App\Controller;

use App\Entity\Events;
use App\Repository\EventsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route(path: '/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function dashboard(EventsRepository $eventsRepository): Response
    {
        $events = $eventsRepository->findBy(['archived' => false], ['startAt' => 'DESC']);
        $events = array_map(function(Events $event) {
            return [
                'id'                   => $event->getId(),
                'name'                 => $event->getName(),
                'date'                 => $event->getStartAt()->format('H:i d-m-Y'),
                'location'             => $event->getLocation(),
                'published'            => $event->isPublished(),
                'nbPlans'              => $event->getNbPersonsByEvent(),
                'reservedAvailability' => $event->reservedAvailability(),
                'nbSubscriptions'      => $event->getSubscriptions()->count(),
                'sendingEmail'         => strlen($event->getSendingEmail()) >  0 ? true : false,
            ];
        }, $events);

        return $this->render('dashboard/index.html.twig', [ "events" => $events]);
    }
}