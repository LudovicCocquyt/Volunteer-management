<?php

namespace App\Security;

use App\Entity\User;

/**
 * Rôles de l'application, du plus faible au plus fort.
 * La hiérarchie (SUPER_ADMIN > ADMIN > USER) est déclarée dans security.yaml.
 */
final class UserRoles
{
    public const USER        = 'ROLE_USER';         // Organisateur : événements, planning, bénévoles
    public const ADMIN       = 'ROLE_ADMIN';        // + gestion des organisateurs
    public const SUPER_ADMIN = 'ROLE_SUPER_ADMIN';  // + gestion des administrateurs

    public const LABELS = [
        self::USER        => 'Organisateur',
        self::ADMIN       => 'Administrateur',
        self::SUPER_ADMIN => 'Super admin',
    ];

    /** Rôles attribuables depuis l'interface (super admin : uniquement en ligne de commande). */
    public const ASSIGNABLE_IN_UI = [self::USER, self::ADMIN];

    private const RANKS = [
        self::USER        => 1,
        self::ADMIN       => 2,
        self::SUPER_ADMIN => 3,
    ];

    public static function highest(User $user): string
    {
        foreach ([self::SUPER_ADMIN, self::ADMIN] as $role) {
            if (in_array($role, $user->getRoles(), true)) {
                return $role;
            }
        }

        return self::USER;
    }

    public static function rank(User $user): int
    {
        return self::RANKS[self::highest($user)];
    }

    /** Valeur à stocker en base : ROLE_USER est toujours ajouté par User::getRoles(). */
    public static function toStoredRoles(string $role): array
    {
        return self::USER === $role ? [] : [$role];
    }
}
