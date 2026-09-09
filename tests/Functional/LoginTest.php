<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginTest extends WebTestCase
{
    private const TEST_EMAIL = 'login-test@grossmp.local';

    protected function setUp(): void
    {
        parent::setUp();

        // Tests share the same SQLite file across runs (no transaction rollback in place yet,
        // see EPIC 12), so make sure a leftover user from a previous run doesn't collide.
        self::bootKernel();
        self::getContainer()->get(EntityManagerInterface::class)
            ->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :email')
            ->setParameter('email', self::TEST_EMAIL)
            ->execute();
        self::ensureKernelShutdown();
    }

    public function testLoginWithValidCredentialsRedirectsToAccountPage(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail(self::TEST_EMAIL);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles([User::ROLE_VALIDATOR]);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        $crawler = $client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => self::TEST_EMAIL,
            '_password' => 'password123',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/account');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Mon compte');
    }

    public function testLoginWithInvalidCredentialsShowsError(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'nobody@grossmp.local',
            '_password' => 'wrong-password',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorExists('.alert-danger');
    }
}
