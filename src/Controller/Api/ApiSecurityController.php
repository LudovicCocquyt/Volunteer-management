<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class ApiSecurityController extends AbstractController
{
    #[Route(path: '/api/current_user', name: 'api_current_user')]
    public function current_user(AuthenticationUtils $authenticationUtils): JsonResponse
    {
        /** @var User[] $current_user */
        $current_user = $this->getUser();
        if (!$current_user) {
            return new JsonResponse(null, 401);
        }

        $current_user = [
            'id'        => $current_user->getId(),
            "roles"     => $current_user->getRoles()
        ];

        return new JsonResponse($current_user);
    }
}
