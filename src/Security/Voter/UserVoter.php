<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\UserRoles;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Règles :
 * - chacun peut modifier son propre compte (sans toucher à son rôle) ;
 * - un utilisateur ne peut gérer que des comptes de rang strictement inférieur
 *   (admin -> organisateurs, super admin -> organisateurs et admins) ;
 * - personne ne peut supprimer son propre compte ;
 * - un super admin ne se supprime/rétrograde qu'en ligne de commande.
 */
final class UserVoter extends Voter
{
    public const EDIT      = 'USER_EDIT';
    public const DELETE    = 'USER_DELETE';
    public const EDIT_ROLE = 'USER_EDIT_ROLE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE, self::EDIT_ROLE], true)
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $me = $token->getUser();
        if (!$me instanceof User) {
            return false;
        }

        /** @var User $subject */
        $isSelf     = $me->getId() === $subject->getId();
        $myRank     = UserRoles::rank($me);
        $targetRank = UserRoles::rank($subject);
        $canManage  = $myRank >= 2 && $myRank > $targetRank;

        return match ($attribute) {
            self::EDIT      => $isSelf || $canManage,
            self::DELETE    => !$isSelf && $canManage,
            self::EDIT_ROLE => !$isSelf && $canManage && UserRoles::SUPER_ADMIN === UserRoles::highest($me),
            default         => false,
        };
    }
}
