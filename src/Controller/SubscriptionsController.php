<?php

namespace App\Controller;

use App\Entity\Subscriptions;
use App\Form\SubscriptionsFormType;
use App\Repository\EventsRepository;
use App\Repository\SubscriptionsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/subscriptions')]
final class SubscriptionsController extends AbstractController
{
    #[Route(name: 'app_subscriptions_index', methods: ['GET'])]
    public function index(SubscriptionsRepository $subscriptionsRepository): Response
    {
        return $this->render('subscriptions/index.html.twig', [
            'subscriptions' => $subscriptionsRepository->findAllByEvent(),
        ]);
    }

    #[Route('/new', name: 'app_subscriptions_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EventsRepository $eventsRepo): Response
    {
        $subscription   = new Subscriptions();
        $form           = $this->createForm(SubscriptionsFormType::class, $subscription);
        $availabilities = [];
        $message        = false;
        $error          = false;

        if (array_key_exists("subscriptions_form", $request->request->all())) {
            $data = $request->request->all();
            // Create the event
            if ($data['subscriptions_form']['event'] > 0) {
                $event = $eventsRepo->find($data['subscriptions_form']['event']);
                unset($data['subscriptions_form']['event']);
            }

            //  Create the availabilities
            if (!empty($data['subscriptions_form']['availabilities'])) {
                $availabilities = json_decode($data['subscriptions_form']['availabilities'], true);
                unset($data['subscriptions_form']['availabilities']);
            }
            $request->request->replace($data);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!isset($event) || count($availabilities) < 1) {
                return $this->render('subscriptions/new.html.twig', [
                    'subscription'      => $subscription,
                    'SubscriptionsForm' => $form,
                    'events'            => $eventsRepo->findPublishedAndNotArchivedEvents(),
                    "message"           => $message,
                    'error'             => true
                ]);
            }

            $subscription->setEvent($event);
            $subscription->setAvailabilities($availabilities);

            $entityManager->persist($subscription);
            $entityManager->flush();
            $message = true;

        }

        return $this->render('subscriptions/new.html.twig', [
            'subscription'      => $subscription,
            'SubscriptionsForm' => $form,
            'events'            => $eventsRepo->findPublishedAndNotArchivedEvents(),
            "message"           => $message,
            'error'             => $error
        ]);
    }

    #[Route('/{id}', name: 'app_subscriptions_show', methods: ['GET'])]
    public function show(Subscriptions $subscription): Response
    {
        return $this->render('subscriptions/show.html.twig', [
            'subscription' => $subscription,
        ]);
    }

    // #[Route('/{id}/edit', name: 'app_subscriptions_edit', methods: ['GET', 'POST'])]
    // public function edit(Request $request, Subscriptions $subscription, EntityManagerInterface $entityManager): Response
    // {
    //     $form = $this->createForm(SubscriptionsFormType::class, $subscription);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager->flush();

    //         return $this->redirectToRoute('app_subscriptions_index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('subscriptions/edit.html.twig', [
    //         'subscription'      => $subscription,
    //         'SubscriptionsForm' => $form,
    //     ]);
    //}

    #[Route('/{id}', name: 'app_subscriptions_delete', methods: ['POST'])]
    public function delete(Request $request, Subscriptions $subscription, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$subscription->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($subscription);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_subscriptions_index', [], Response::HTTP_SEE_OTHER);
    }
}
