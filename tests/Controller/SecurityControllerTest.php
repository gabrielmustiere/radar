<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityControllerTest extends WebTestCase
{
    public function testAnonymousUserIsRedirectedToLogin(): void
    {
        $client = self::createClient();

        $client->request('GET', '/');

        self::assertResponseRedirects('/login');
    }

    public function testLoginPageIsDisplayed(): void
    {
        $client = self::createClient();

        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Se connecter');
        // Identifiants de test réservés à l'environnement dev
        self::assertSelectorTextNotContains('body', 'admin@example.com');
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');

        $client->submitForm('Se connecter', [
            '_username' => 'admin@example.com',
            '_password' => 'password',
        ]);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Tous les liens');
    }

    public function testRememberMeOutlivesTheSession(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');

        $client->submitForm('Se connecter', [
            '_username' => 'admin@example.com',
            '_password' => 'password',
            '_remember_me' => true,
        ]);

        $rememberMe = $client->getCookieJar()->get('REMEMBERME');
        self::assertNotNull($rememberMe);
        self::assertGreaterThan(time() + (29 * 86_400), (int) $rememberMe->getExpiresTime());

        // Session perdue (navigateur fermé, fichier nettoyé) : seul le cookie de longue durée reste.
        $client->getCookieJar()->clear();
        $client->getCookieJar()->set($rememberMe);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }

    public function testNoRememberMeCookieWhenUnchecked(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');

        $client->submitForm('Se connecter', [
            '_username' => 'admin@example.com',
            '_password' => 'password',
        ]);

        self::assertNull($client->getCookieJar()->get('REMEMBERME'));
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');

        $client->submitForm('Se connecter', [
            '_username' => 'admin@example.com',
            '_password' => 'wrong-password',
        ]);

        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorExists('[role="alert"]');
    }

    public function testLogout(): void
    {
        $client = self::createClient();
        $userRepository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $userRepository);
        $user = $userRepository->findOneBy(['email' => 'admin@example.com']);
        self::assertNotNull($user);
        $client->loginUser($user);

        $client->request('GET', '/logout');
        $client->request('GET', '/');

        self::assertResponseRedirects('/login');
    }
}
