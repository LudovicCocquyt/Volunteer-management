<?php

namespace App\Controller;

use App\Entity\Events;
use App\Repository\PlansRepository;
use App\Repository\SubscriptionsRepository;
use App\Service\ExcelExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/export')]
final class ExportController extends AbstractController
{
    public function __construct(private readonly ExcelExporter $excelExporter)
    {
    }

    // Même nom et même URL qu'avant : le lien du template subscriptions/index ne change pas.
    #[Route('/{id}', name: 'export_excel', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function subscriptions(Events $event, SubscriptionsRepository $subscriptionsRepository): Response
    {
        return $this->excelExporter->exportSubscriptions(
            $event,
            $subscriptionsRepository->findByEvent($event->getId())
        );
    }

    // Remplace l'export "Liste.xls" généré auparavant dans le navigateur avec exceljs.
    #[Route('/planning/{id}', name: 'export_planning_excel', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function planning(Events $event, PlansRepository $plansRepository): Response
    {
        return $this->excelExporter->exportPlanning(
            $event,
            $plansRepository->findBy(['event' => $event], ['activityName' => 'ASC', 'startDate' => 'ASC'])
        );
    }
}
