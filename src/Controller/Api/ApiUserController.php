<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\UserRoles;
use App\Security\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
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
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        $params   = $request->toArray();
        $password = (string) ($params['password'] ?? '');
        $role     = (string) ($params['role'] ?? UserRoles::USER);

        // Un admin ne crée que des organisateurs ; seul un super admin peut créer un admin
        if (!in_array($role, UserRoles::ASSIGNABLE_IN_UI, true)
            || (UserRoles::USER !== $role && !$this->isGranted(UserRoles::SUPER_ADMIN))) {
            return $this->json(['status' => 'Error', 'errors' => ["Vous ne pouvez pas attribuer ce rôle."]], JsonResponse::HTTP_FORBIDDEN);
        }

        $user = new User();
        $user->setEmail(trim((string) ($params['email'] ?? '')));
        $user->setFirstname(trim((string) ($params['firstname'] ?? '')));
        $user->setLastname(trim((string) ($params['lastname'] ?? '')));
        $user->setRoles(UserRoles::toStoredRoles($role));

        $violations = $validator->validate($user);
        $violations->addAll($validator->validate($password, [
            new Assert\NotBlank(message: 'Le mot de passe est obligatoire.'),
            new Assert\Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
        ]));

        if (count($violations) > 0) {
            return $this->json(['status' => 'Error', 'errors' => $this->messages($violations)], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['status' => 'User created!', 'user' => $this->normalize($user)]);
    }

    #[Route('/delete_user/{id}', name: 'api_delete_user', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted(UserVoter::DELETE, $user)) {
            return $this->json(['status' => 'Error', 'errors' => ["Vous ne pouvez pas supprimer ce compte."]], JsonResponse::HTTP_FORBIDDEN);
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

    /** @return string[] */
    private function messages(ConstraintViolationListInterface $violations): array
    {
        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = $violation->getMessage();
        }

        return $messages;
    }
}
