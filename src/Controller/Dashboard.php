<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class Dashboard extends AbstractController
{
    #[Route(path: '/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('Dashboard/index.html.twig', []);
    }
}