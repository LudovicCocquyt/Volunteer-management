<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/users', name: 'app_users')]
    public function index(UserRepository $userRepo): Response
    {
        $users = $userRepo->findAll();
        return $this->render('user/list.html.twig', [
            'controller_name' => 'UserController',
            'users'           =>  $users
        ]);
    }
}
