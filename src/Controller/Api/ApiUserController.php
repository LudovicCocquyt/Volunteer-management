<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\RegistrationFormType;


class ApiUserController extends AbstractController
{
    #[Route('/api/users', name: 'api_users', methods: ['GET'])]
    public function index(UserRepository $userRepo): JsonResponse
    {
        $users = $userRepo->findAll();
        $users = array_map(function(User $user) {
            return [
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname'  => $user->getLastname(),
                "roles"     => $user->getRoles()
            ];
        }, $users);
        return new JsonResponse($users);
    }

    #[Route('/api/add_user', name: 'api_add_user', methods: ['POST'])]
    public function addUser(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $params = json_decode($request->getContent(), true);

            $user = new User();
            $user->setEmail($params['email']);
            $user->setFirstname($params['firstname']);
            $user->setLastname($params['lastname']);
            $user->setPassword($userPasswordHasher->hashPassword($user, $params['password']));

            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse(['status' => 'User created!'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'Error', "message" => $e->getmessage()], 400);
        }
    }

    #[Route('/api/delete_user/{id}', name: 'api_delete_user', methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new JsonResponse(['status' => 'Error'], 403);
        }
        try {
            $entityManager->remove($user);
            $entityManager->flush();
            return new JsonResponse(['status' => 'User deleted!'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'Error', "message" => $e->getmessage()], 400);
        }
    }
}
