<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Security\UserRoles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Nécessite la base de test (voir README, section Tests).
 */
final class AccessControlTest extends WebTestCase
{
    private KernelBrowser $client;

    /** @var int[] */
    private array $createdUserIds = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        if ($this->createdUserIds) {
            static::getContainer()->get(EntityManagerInterface::class)
                ->createQuery('DELETE FROM App\Entity\User u WHERE u.id IN (:ids)')
                ->setParameter('ids', $this->createdUserIds)
                ->execute();
        }

        parent::tearDown();
    }

    public function testAnonymousCannotReachTheBackOffice(): void
    {
        foreach (['/api/events', '/api/users', '/users', '/attachments/', '/api/plans/our_needs_by_event/1'] as $url) {
            $this->client->request('GET', $url);
            self::assertTrue($this->client->getResponse()->isRedirect(), "$url doit rediriger vers la connexion");
        }
    }

    public function testOldPublicEventsListIsGone(): void
    {
        $this->client->request('GET', '/api/public/events');
        self::assertResponseStatusCodeSame(404);
    }

    public function testPublicNeedsOfUnknownEventIs404(): void
    {
        $this->client->request('GET', '/api/public/plans/our_needs_by_event/999999999');
        self::assertResponseStatusCodeSame(404);
    }

    public function testOrganizerCannotManageUsers(): void
    {
        $organizer = $this->createUser(UserRoles::USER);
        $other     = $this->createUser(UserRoles::USER);
        $this->client->loginUser($organizer);

        $this->client->request('GET', '/api/users');
        self::assertResponseStatusCodeSame(403);

        $this->client->request('DELETE', '/api/delete_user/' . $other->getId());
        self::assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/user/edit/' . $other->getId());
        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminListsUsersButCannotCreateAnAdmin(): void
    {
        $admin = $this->createUser(UserRoles::ADMIN);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/api/users');
        self::assertResponseIsSuccessful();

        $this->client->request('POST', '/api/add_user', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email'     => uniqid('nouvel_admin_') . '@example.test',
            'firstname' => 'Test',
            'lastname'  => 'Test',
            'password'  => 'motdepasse-solide',
            'role'      => UserRoles::ADMIN,
        ]));
        self::assertResponseStatusCodeSame(403);
    }

    private function createUser(string $role): User
    {
        $user = (new User())
            ->setEmail(uniqid('test_') . '@example.test')
            ->setFirstname('Test')
            ->setLastname('Test')
            ->setPassword('non-utilise')
            ->setRoles(UserRoles::toStoredRoles($role));

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();
        $this->createdUserIds[] = $user->getId();

        return $user;
    }
}
