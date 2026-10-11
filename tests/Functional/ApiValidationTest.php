<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Security\UserRoles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Format des erreurs de l'API : {status: "Error", message, errors: [{field, message}]}.
 * Nécessite la base de test (voir README, section Tests).
 */
final class ApiValidationTest extends WebTestCase
{
    private KernelBrowser $client;

    /** @var int[] */
    private array $createdUserIds = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->loginUser($this->createUser(UserRoles::ADMIN));
    }

    protected function tearDown(): void
    {
        static::getContainer()->get(EntityManagerInterface::class)
            ->createQuery('DELETE FROM App\Entity\User u WHERE u.id IN (:ids)')
            ->setParameter('ids', $this->createdUserIds)
            ->execute();

        parent::tearDown();
    }

    public function testEmptyPlanIsRejectedWithFieldErrors(): void
    {
        $data = $this->postJson('/api/plan/new', []);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('Error', $data['status']);
        $fields = array_column($data['errors'], 'field');
        self::assertContains('eventId', $fields);
        self::assertContains('resourceId', $fields);
    }

    public function testPlanEndingBeforeItStartsIsRejected(): void
    {
        $data = $this->postJson('/api/plan/new', [
            'eventId'    => 1,
            'resourceId' => 'Buvette',
            'start'      => '2026-06-27',
            'startTime'  => '11:00',
            'endTime'    => '09:00',
            'nbPers'     => '2',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString("heure de fin doit être après", $data['message']);
    }

    public function testMalformedJsonGivesAReadableError(): void
    {
        $this->client->request('POST', '/api/plan/new', server: ['CONTENT_TYPE' => 'application/json'], content: '{pas du json');

        self::assertResponseStatusCodeSame(400);
        self::assertSame('Requête invalide.', json_decode($this->client->getResponse()->getContent(), true)['message']);
    }

    public function testUserCreationValidatesEmail(): void
    {
        $data = $this->postJson('/api/add_user', [
            'email'     => 'pas-un-email',
            'firstname' => 'Test',
            'lastname'  => 'Test',
            'password'  => 'motdepasse-solide',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('email', array_column($data['errors'], 'field'));
    }

    private function postJson(string $url, array $payload): array
    {
        $this->client->request('POST', $url, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload));

        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
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
