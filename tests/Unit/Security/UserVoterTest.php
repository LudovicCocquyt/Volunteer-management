<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserRoles;
use App\Security\Voter\UserVoter;
use App\Tests\Support\EntityIdTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class UserVoterTest extends TestCase
{
    use EntityIdTrait;

    /**
     * [rôle de l'utilisateur connecté, rôle du compte visé, attribut, accès attendu]
     */
    public static function rules(): iterable
    {
        $u = UserRoles::USER;
        $a = UserRoles::ADMIN;
        $s = UserRoles::SUPER_ADMIN;

        yield 'organisateur ne modifie pas un autre organisateur' => [$u, $u, UserVoter::EDIT, false];
        yield 'admin modifie un organisateur'                     => [$a, $u, UserVoter::EDIT, true];
        yield 'admin ne modifie pas un admin'                     => [$a, $a, UserVoter::EDIT, false];
        yield 'admin supprime un organisateur'                    => [$a, $u, UserVoter::DELETE, true];
        yield 'admin ne change pas de rôle'                       => [$a, $u, UserVoter::EDIT_ROLE, false];
        yield 'super admin change le rôle d\'un admin'            => [$s, $a, UserVoter::EDIT_ROLE, true];
        yield 'super admin ne supprime pas un super admin'        => [$s, $s, UserVoter::DELETE, false];
    }

    #[DataProvider('rules')]
    public function testRules(string $myRole, string $targetRole, string $attribute, bool $granted): void
    {
        $me     = $this->user(1, $myRole);
        $target = $this->user(2, $targetRole);

        self::assertSame(
            $granted ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED,
            (new UserVoter())->vote($this->token($me), $target, [$attribute])
        );
    }

    public function testEveryoneEditsOwnAccountButCannotDeleteItOrChangeOwnRole(): void
    {
        foreach ([UserRoles::USER, UserRoles::ADMIN, UserRoles::SUPER_ADMIN] as $role) {
            $me    = $this->user(1, $role);
            $voter = new UserVoter();

            self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($me), $me, [UserVoter::EDIT]), $role);
            self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token($me), $me, [UserVoter::DELETE]), $role);
            self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token($me), $me, [UserVoter::EDIT_ROLE]), $role);
        }
    }

    private function user(int $id, string $role): User
    {
        $user = (new User())
            ->setEmail("user$id@example.test")
            ->setRoles(UserRoles::toStoredRoles($role));
        self::setId($user, $id);

        return $user;
    }

    private function token(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }
}
