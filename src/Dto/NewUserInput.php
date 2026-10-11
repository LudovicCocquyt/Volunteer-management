<?php

namespace App\Dto;

use App\Security\UserRoles;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Création d'un compte depuis la page Utilisateurs (POST /api/add_user).
 * L'unicité de l'email est vérifiée ensuite par la validation de l'entité User.
 */
final class NewUserInput
{
    public function __construct(
        #[Assert\NotBlank(message: "L'email est obligatoire.")]
        #[Assert\Email(message: "L'email n'est pas valide.")]
        #[Assert\Length(max: 180)]
        public readonly ?string $email = null,

        #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
        #[Assert\Length(max: 255)]
        public readonly ?string $firstname = null,

        #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
        #[Assert\Length(max: 255)]
        public readonly ?string $lastname = null,

        #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
        #[Assert\Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')]
        public readonly ?string $password = null,

        #[Assert\Choice(choices: UserRoles::ASSIGNABLE_IN_UI, message: 'Rôle inconnu.')]
        public readonly string $role = UserRoles::USER,
    ) {
    }
}
