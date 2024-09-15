<?php

namespace App\Controller;

use App\Entity\Activities;
use App\Form\ActivitiesFormType;
use App\Form\ActivitiesType;
use App\Repository\ActivitiesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activities')]
final class ActivitiesController extends AbstractController
{
    #[Route(name: 'app_activities', methods: ['GET'])]
    public function index(ActivitiesRepository $activitiesRepository): Response
    {
        return $this->render('activities/activities.html.twig', [
            'activities' => $activitiesRepository->findAll(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_edit_activities', methods: ['GET', 'POST'])]
    public function edit(Request $request, ActivitiesRepository $activityRepo, EntityManagerInterface $entityManager): Response
    {
        $activity = $activityRepo->find($request->get('id'));
        $form = $this->createForm(ActivitiesFormType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($activity);
            $entityManager->flush();

            return $this->redirectToRoute('app_activities');
        }

        return $this->render('activities/edit.html.twig', [
            'activity'       => $activity,
            'ActivitiesForm' => $form,
        ]);
    }
}
