<?php

namespace App\Controller;

use App\Entity\Events;
use App\Form\EventsFormType;
use App\Repository\EventsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events')]
final class EventsController extends AbstractController
{
    #[Route('/archives', name: 'app_events', methods: ['GET'])]
    public function index(EventsRepository $eventsRepository): Response
    {
        return $this->render('events/archived.html.twig', [
            'events' => $eventsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_events_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Events();
        $form = $this->createForm(EventsFormType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($event);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('events/new.html.twig', [
            'event'      => $event,
            'EventsForm' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_events_show', methods: ['GET'])]
    public function show(Events $event): Response
    {
        return $this->render('events/show.html.twig', [
            'event'                => $event,
            'nbPlans'              => $event->getNbPersonsByEvent(),
            'reservedAvailability' => $event->reservedAvailability(),
            'nbSubscriptions'      => $event->getSubscriptions()->count(),
            'canDelete'            => (count($event->getPlans()) < 1 && $event->getSubscriptions()->isEmpty())
        ]);
    }

    #[Route('/{id}/edit', name: 'app_events_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Events $event, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EventsFormType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_events_edit', ['id' => $event->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('events/edit.html.twig', [
            'event'                => $event,
            'EventsForm'           => $form,
            'nbPlans'              => $event->getNbPersonsByEvent(),
            'reservedAvailability' => $event->reservedAvailability(),
            'nbSubscriptions'      => $event->getSubscriptions()->count(),
            'canDelete'            => (count($event->getPlans()) < 1 && $event->getSubscriptions()->isEmpty())
        ]);
    }

    #[Route('/{id}', name: 'app_events_delete', methods: ['POST'])]
    public function delete(Request $request, Events $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
    }
}
