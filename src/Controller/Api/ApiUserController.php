<?php

namespace App\Controller\Api;

use App\Dto\NewUserInput;
use App\Entity\User;
use App\Http\ApiError;
use App\Repository\UserRepository;
use App\Security\UserRoles;
use App\Security\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
#[IsGranted(UserRoles::ADMIN)]
final class ApiUserController extends AbstractController
{
    #[Route('/users', name: 'api_users', methods: ['GET'])]
    public function index(UserRepository $userRepo): JsonResponse
    {
        $users = $userRepo->findBy([], ['lastname' => 'ASC', 'firstname' => 'ASC']);

        return $this->json(array_map(fn (User $user) => $this->normalize($user), $users));
    }

    #[Route('/add_user', name: 'api_add_user', methods: ['POST'])]
    public function addUser(
        #[MapRequestPayload] NewUserInput $input,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        // Un admin ne crée que des organisateurs ; seul un super admin peut créer un admin
        if (UserRoles::USER !== $input->role && !$this->isGranted(UserRoles::SUPER_ADMIN)) {
            return ApiError::response('Vous ne pouvez pas attribuer ce rôle.', JsonResponse::HTTP_FORBIDDEN);
        }

        $user = (new User())
            ->setEmail(trim($input->email))
            ->setFirstname(trim($input->firstname))
            ->setLastname(trim($input->lastname))
            ->setRoles(UserRoles::toStoredRoles($input->role));

        // Unicité de l'email (contrainte UniqueEntity de l'entité)
        $violations = $validator->validate($user);
        if (count($violations) > 0) {
            return ApiError::fromViolations($violations);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $input->password));
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['status' => 'User created!', 'user' => $this->normalize($user)]);
    }

    #[Route('/delete_user/{id}', name: 'api_delete_user', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted(UserVoter::DELETE, $user)) {
            return ApiError::response('Vous ne pouvez pas supprimer ce compte.', JsonResponse::HTTP_FORBIDDEN);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(['status' => 'User deleted!']);
    }

    private function normalize(User $user): array
    {
        $role = UserRoles::highest($user);

        return [
            'id'        => $user->getId(),
            'email'     => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname'  => $user->getLastname(),
            'role'      => $role,
            'roleLabel' => UserRoles::LABELS[$role],
            'canEdit'   => $this->isGranted(UserVoter::EDIT, $user),
            'canDelete' => $this->isGranted(UserVoter::DELETE, $user),
        ];
    }
}
