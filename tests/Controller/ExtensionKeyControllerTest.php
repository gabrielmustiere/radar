<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ExtensionKeyControllerTest extends WebTestCase
{
    public function testMemberGeneratesAKeyShownOnceThenOnlyItsDates(): void
    {
        $member = $this->member();
        $client = $this->clientFor($member);

        $crawler = $client->request('GET', '/extension');
        self::assertSelectorExists('[data-test="extension-key-none"]');
        self::assertSelectorTextContains('[data-test="extension-key-generate"]', 'Générer une clé');
        $client->submit($crawler->filter('[data-test="extension-key-generate"]')->form());

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Cache-Control', 'no-store, private');
        $key = $client->getCrawler()->filter('[data-test="extension-key-value"]')->text();
        self::assertSame(hash('sha256', $key), $this->reload($member)->getExtensionKeyHash());

        $client->request('GET', '/extension');
        self::assertSelectorNotExists('[data-test="extension-key-value"]');
        self::assertSelectorExists('[data-test="extension-key-created-at"]');
        self::assertSelectorTextContains('[data-test="extension-key-last-used-at"]', 'jamais');
        self::assertStringNotContainsString($key, (string) $client->getResponse()->getContent());
    }

    public function testRegeneratingReplacesTheKey(): void
    {
        $member = $this->member();
        $client = $this->clientFor($member);
        $crawler = $client->request('GET', '/extension');
        $client->submit($crawler->filter('[data-test="extension-key-generate"]')->form());
        $previous = (string) $this->reload($member)->getExtensionKeyHash();

        $crawler = $client->request('GET', '/extension');
        $client->submit($crawler->filter('[data-test="extension-key-generate"]')->form());

        $key = $client->getCrawler()->filter('[data-test="extension-key-value"]')->text();
        self::assertNotSame($previous, $this->reload($member)->getExtensionKeyHash());
        self::assertSame(hash('sha256', $key), $this->reload($member)->getExtensionKeyHash());
    }

    public function testKeyIdleForMoreThanNinetyDaysIsShownAsExpired(): void
    {
        $member = $this->member();
        $client = $this->clientFor($member);
        $crawler = $client->request('GET', '/extension');
        $client->submit($crawler->filter('[data-test="extension-key-generate"]')->form());
        $idle = $this->reload($member);
        $idle->markExtensionKeyUsed(new \DateTimeImmutable('-91 days'));
        $this->users()->save($idle);

        $client->request('GET', '/extension');

        self::assertSelectorExists('[data-test="extension-key-expired"]');
        self::assertSelectorTextContains('[data-test="extension-key-generate"]', 'Régénérer');
    }

    public function testRevokingRemovesTheKey(): void
    {
        $member = $this->member();
        $client = $this->clientFor($member);
        $crawler = $client->request('GET', '/extension');
        $client->submit($crawler->filter('[data-test="extension-key-generate"]')->form());

        $client->submit($client->getCrawler()->filter('[data-test="extension-key-revoke"]')->form());

        self::assertResponseRedirects('/extension');
        $client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'Clé révoquée');
        self::assertSelectorExists('[data-test="extension-key-none"]');
        self::assertNull($this->reload($member)->getExtensionKeyHash());
    }

    public function testGenerationRequiresACsrfToken(): void
    {
        $member = $this->member();
        $client = $this->clientFor($member);

        $client->request('POST', '/extension/generer', ['_token' => 'invalide']);

        // Un jeton CSRF invalide est une erreur d'authentification : le firewall renvoie vers la connexion.
        self::assertResponseRedirects('http://localhost/login');
        self::assertNull($this->reload($member)->getExtensionKeyHash());
    }

    public function testAnonymousVisitorIsSentToLogin(): void
    {
        $client = self::createClient();

        $client->request('GET', '/extension');

        self::assertResponseRedirects('http://localhost/login');
    }

    private function member(): User
    {
        self::ensureKernelShutdown();
        self::createClient();
        $user = new User()
            ->setEmail(uniqid('cle-') . '@example.com')
            ->setPassword('!');
        $this->users()->save($user);

        return $user;
    }

    private function clientFor(User $user): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        return $client;
    }

    private function reload(User $user): User
    {
        $reloaded = $this->users()->findOneByEmail((string) $user->getEmail());
        self::assertNotNull($reloaded);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->refresh($reloaded);

        return $reloaded;
    }

    private function users(): UserRepository
    {
        $repository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);

        return $repository;
    }
}
