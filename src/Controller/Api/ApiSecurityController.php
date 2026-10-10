<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Security\UserRoles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiSecurityController extends AbstractController
{
    #[Route(path: '/api/current_user', name: 'api_current_user', methods: ['GET'])]
    public function currentUser(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(null, JsonResponse::HTTP_UNAUTHORIZED);
        }

        $assignableRoles = [];
        if ($this->isGranted(UserRoles::SUPER_ADMIN)) {
            foreach (UserRoles::ASSIGNABLE_IN_UI as $role) {
                $assignableRoles[$role] = UserRoles::LABELS[$role];
            }
        }

        return $this->json([
            'id'              => $user->getId(),
            'role'            => UserRoles::highest($user),
            'canManageUsers'  => $this->isGranted(UserRoles::ADMIN),
            'assignableRoles' => $assignableRoles,
        ]);
    }
}
