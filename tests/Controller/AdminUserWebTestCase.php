<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Enum\Type\Team;
use App\Repository\UserRepository;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Back office des comptes. L'admin permanent des tests est `admin@example.com` (`.env.test`) ; chaque test crée ses
 * propres comptes pour ne pas toucher aux membres des fixtures.
 */
abstract class AdminUserWebTestCase extends SourceWebTestCase
{
    protected const string PASSWORD = 'ancien-mot-de-passe';

    /** @return non-empty-string */
    protected function editPath(User $user): string
    {
        return sprintf('/admin/comptes/%d', (int) $user->getId());
    }

    protected function account(?Team $team = null, bool $admin = false): User
    {
        return $this->userManager()->create(uniqid('compte-') . '@example.com', self::PASSWORD, $team, $admin);
    }

    protected function user(string $email): User
    {
        $this->entityManager()->clear();
        $repository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);
        $user = $repository->findOneByEmail($email);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    protected function userManager(): UserManager
    {
        $manager = self::getContainer()->get(UserManager::class);
        self::assertInstanceOf(UserManager::class, $manager);

        return $manager;
    }

    protected function row(Crawler $crawler, string $email): Crawler
    {
        $row = $crawler
            ->filter('[data-test="user-row"]')
            ->reduce(static fn(Crawler $node): bool => $node->filter('[data-test="user-email"]')->text() === $email);
        self::assertCount(1, $row, sprintf('Compte %s absent de la liste.', $email));

        return $row;
    }

    protected function rowButton(KernelBrowser $client, User $user, string $button): Crawler
    {
        return $this->row($client->request('GET', '/admin/comptes'), $user->getUserIdentifier())->filter(sprintf(
            '[data-test="%s"]',
            $button,
        ));
    }

    protected function deactivateToken(Crawler $crawler): string
    {
        return (string) $crawler
            ->filter('[data-test="user-deactivate"]')
            ->first()
            ->closest('form')
            ?->filter('input[name="_token"]')
            ->attr('value');
    }

    /** Connexion par le formulaire, la seule qui passe par le `UserChecker` (`loginUser()` le contourne). */
    protected function logInWithForm(
        KernelBrowser $client,
        string $email,
        #[\SensitiveParameter]
        string $password,
    ): void {
        $client->getCookieJar()->clear();
        $client->request('GET', '/login');
        $client->submitForm('Se connecter', ['_username' => $email, '_password' => $password]);
    }
}
