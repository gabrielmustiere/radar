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
    protected function submit(KernelBrowser $client, array $values): void
    {
        $crawler = $client->request('GET', '/liens/ajouter');
        $token = $crawler->filter('input[name="link[_token]"]')->attr('value');

        $client->request('POST', '/liens/ajouter', ['link' => [...$values, '_token' => $token]]);
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
