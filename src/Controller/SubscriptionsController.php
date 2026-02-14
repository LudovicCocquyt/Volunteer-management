<?php

namespace App\Controller;

use App\Entity\Subscriptions;
use App\Form\SubscriptionsFormType;
use App\Repository\EventsRepository;
use App\Repository\SubscriptionsRepository;
use App\Service\ExcelExporter;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ManageVolunteerSercive;

final class SubscriptionsController extends AbstractController
{
    private $excelExporter;

    private $emailService;

    public function __construct(ExcelExporter $excelExporter, EmailService $emailService)
    {
        $this->excelExporter              = $excelExporter;
        $this->emailService               = $emailService;
    }

    #[Route('/export/{id}', name: 'export_excel', methods: ['GET'])]
    public function export(Request $request, SubscriptionsRepository $subscriptionsRepository): Response
    {
        $subscriptions = $subscriptionsRepository->findByEvent($request->get('id'));
        foreach ($subscriptions as $subscription) {
            $export[] = [
                $subscription->getId(),
                $subscription->getFirstname(),
                $subscription->getLastname(),
                $subscription->getEmail(),
                $subscription->getPhone(),
                $subscription->getChildName(),
                $subscription->getClass(),
            ];
        }
        $content = $this->excelExporter->export($export);

        return new Response(
            $content,
            200,
            [
                'Content-Type'        => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export.xls"',
            ]
        );
    }

    #[Route('/subscriptions', name: 'app_subscriptions_index', methods: ['GET'])]
    public function index(SubscriptionsRepository $subscriptionsRepository): Response
    {
        return $this->render('subscriptions/index.html.twig', [
            'subscriptionByEvent' => $subscriptionsRepository->findAllByEvents(),
        ]);
    }

    #[Route('/public/subscriptions/new', name: 'app_subscriptions_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EventsRepository $eventsRepo, ManageVolunteerSercive $manageVolunteer): Response
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
                    'events'            => $eventsRepo->findPublishedEvents(),
                    "message"           => $message,
                    'error'             => true
                ]);
            }

            $subscription->setEvent($event);
            $subscription->setAvailabilities($availabilities);

            $entityManager->persist($subscription);
            $entityManager->flush();
            $message = true;

            // Sending an email to the volunteer to confirm their registration
            $volunteerParams = $this->emailService->prepareEmailToVolunteerAfterSubscription($subscription, $event);
            if ($volunteerParams) {
                $this->emailService->send($volunteerParams);
            }

            // Sending an email to the admin to inform them of a new subscription
            if (!empty($event->getManagerNotification())) {
                // Prepare the email to be sent to the admin after subscription
                $params = $this->emailService->prepareEmailToAdminAfterSubscription($subscription, $event);
                $succes = !empty($params) ? $this->emailService->send($params) : null;
            }
            // If the event is set to automatic scheduling, try to assign the volunteer automatically
            if ($event->isSchedulingAuto()) {
                $result = $manageVolunteer->assignAuto($subscription);
                // If the assignment fails, notify the admin by email
                if (!$result && !empty($event->getManagerNotification())) {
                    // If the automatic assignment fails, send an error email to the admin
                    $adminParams = $this->emailService->prepareEmailToAdminAutoAssignFailed($subscription, $event);
                    if ($adminParams) {
                        $this->emailService->send($adminParams);
                    }
                }
            }
        }

        return $this->render('subscriptions/new.html.twig', [
            'subscription'      => $subscription,
            'SubscriptionsForm' => $form,
            'events'            => $eventsRepo->findPublishedEvents(),
            "message"           => $message,
            'error'             => $error,
            'mailSent'          => $succes ?? null
        ]);
    }

    #[Route('/subscriptions/{id}/edit', name: 'app_subscriptions_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Subscriptions $subscription, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SubscriptionsFormType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if the form has availabilities and replace the data
            $data           = $request->request->all();
            $availabilities = json_decode($data['subscriptions_form']['availabilities'], true);
            unset($data['subscriptions_form']['availabilities']);
            $subscription->setAvailabilities($availabilities);

            $entityManager->flush();

            return $this->redirectToRoute('app_subscriptions_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('subscriptions/edit.html.twig', [
            'subscription'      => $subscription,
            'SubscriptionsForm' => $form,
            'eventId'           => $subscription->getEvent() ? $subscription->getEvent()->getId() : null,
        ]);
    }

    #[Route('/subscriptions/{id}', name: 'app_subscriptions_delete', methods: ['POST'])]
    public function delete(Request $request, Subscriptions $subscription, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$subscription->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($subscription);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_subscriptions_index', [], Response::HTTP_SEE_OTHER);
    }
}
