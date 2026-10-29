<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Parcours d'ajout de lien en tant que membre connecté, sur les fixtures de test.
 */
abstract class LinkWebTestCase extends WebTestCase
{
    /** Carte la plus récente du fil d'accueil (premier jour, première carte). */
    protected const string FIRST_CARD = '[data-test="feed-day"]:first-child [data-test="link-card"]:first-child';

    protected function loggedClient(): KernelBrowser
    {
        $client = self::createClient();
        $repository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);
        $user = $repository->findOneBy(['email' => 'admin@example.com']);
        self::assertNotNull($user);
        $client->loginUser($user);

        return $client;
    }

    /** @param array<string, mixed> $values */
    protected function submit(
        KernelBrowser $client,
        array $values,
        string $path = '/liens/ajouter',
        string $form = 'link',
    ): void {
        $crawler = $client->request('GET', $path);
        $token = $crawler->filter(sprintf('input[name="%s[_token]"]', $form))->attr('value');

        $client->request('POST', $path, [$form => [...$values, '_token' => $token]]);
    }

    protected function categoryId(string $name): int
    {
        $repository = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $repository);
        foreach ($repository->findAllOrderedByName() as $category) {
            if ($category->getName() === $name) {
                return (int) $category->getId();
            }
        }

        self::fail(sprintf('Catégorie « %s » absente des fixtures.', $name));
    }
}
