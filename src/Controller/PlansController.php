<?php

namespace App\Controller;

use App\Entity\Plans;
use App\Form\PlansType;
use App\Repository\PlansRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/plans')]
final class PlansController extends AbstractController
{
    #[Route(name: 'app_plans_index', methods: ['GET'])]
    public function index(PlansRepository $plansRepository): Response
    {
        return $this->render('plans/index.html.twig', [
            'plans' => $plansRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_plans_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $plan = new Plans();
        $form = $this->createForm(PlansType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($plan);
            $entityManager->flush();

            return $this->redirectToRoute('app_plans_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('plans/new.html.twig', [
            'plan' => $plan,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_plans_show', methods: ['GET'])]
    public function show(Plans $plan): Response
    {
        return $this->render('plans/show.html.twig', [
            'plan' => $plan,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_plans_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Plans $plan, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PlansType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_plans_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('plans/edit.html.twig', [
            'plan' => $plan,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_plans_delete', methods: ['POST'])]
    public function delete(Request $request, Plans $plan, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$plan->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($plan);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_plans_index', [], Response::HTTP_SEE_OTHER);
    }
}
