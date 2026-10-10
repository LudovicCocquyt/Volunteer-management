<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserFormType;
use App\Security\UserRoles;
use App\Security\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/users', name: 'app_users', methods: ['GET'])]
    #[IsGranted(UserRoles::ADMIN)]
    public function index(): Response
    {
        return $this->render('user/users.html.twig');
    }

    #[Route('/user/edit/{id}', name: 'app_edit_users', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

        $canEditRole = $this->isGranted(UserVoter::EDIT_ROLE, $user);
        $form        = $this->createForm(UserFormType::class, $user, ['can_edit_role' => $canEditRole]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string|null $plainPassword */
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            }

            if ($canEditRole) {
                $user->setRoles(UserRoles::toStoredRoles($form->get('role')->getData()));
            }

            $entityManager->flush();

            return $this->isGranted(UserRoles::ADMIN)
                ? $this->redirectToRoute('app_users')
                : $this->redirectToRoute('app_dashboard');
        }

        return $this->render('user/edit.html.twig', [
            'user'     => $user,
            'UserForm' => $form,
            'roleLabel' => UserRoles::LABELS[UserRoles::highest($user)],
            'isSelf'   => $this->getUser() instanceof User && $this->getUser()->getId() === $user->getId(),
        ]);
    }
}
