<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;

final class LinkDuplicateTest extends LinkWebTestCase
{
    public function testDuplicateIsRefusedWithExistingCard(): void
    {
        $client = $this->loggedClient();
        $slug = uniqid('doublon-');
        $category = 'Catégorie du doublon ' . uniqid();
        $this->submit($client, [
            'url' => 'https://example.com/' . $slug,
            'categories' => [$this->categoryId('Tester une app mobile')],
        ]);
        self::assertResponseRedirects('/');

        $this->submit($client, [
            'url' => 'http://www.example.com/' . $slug . '/?utm_source=chat#haut',
            'newCategory' => $category,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="link-duplicate"]', 'Ce lien est déjà dans Radar');
        self::assertSelectorTextContains(
            '[data-test="link-duplicate"] [data-test="link-card"]',
            'https://example.com/' . $slug,
        );
        self::assertSelectorTextContains('[data-test="link-form"]', 'Ce lien est déjà dans Radar.');
        $repository = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $repository);
        $names = array_map(static fn(Category $c): string => $c->getName(), $repository->findAllOrderedByName());
        self::assertNotContains($category, $names);

        $client->clickLink('Voir la carte');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-test="link-card"]', 'https://example.com/' . $slug);
    }

    public function testYoutubeShortLinkIsADuplicate(): void
    {
        $client = $this->loggedClient();
        $id = uniqid('vid');
        $this->submit($client, [
            'url' => 'https://www.youtube.com/watch?v=' . $id,
            'categories' => [$this->categoryId('Tester une app mobile')],
        ]);

        $this->submit($client, [
            'url' => 'https://youtu.be/' . $id,
            'categories' => [$this->categoryId('Tester une app mobile')],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('[data-test="link-duplicate"]');
    }

    public function testSignificantParameterMakesADistinctLink(): void
    {
        $client = $this->loggedClient();
        $path = 'https://example.com/' . uniqid('liste-');
        $this->submit($client, [
            'url' => $path . '?page=1',
            'categories' => [$this->categoryId('Tester une app mobile')],
        ]);

        $this->submit($client, [
            'url' => $path . '?page=2',
            'categories' => [$this->categoryId('Tester une app mobile')],
        ]);

        self::assertResponseRedirects('/');
    }

    public function testUnknownCardIsNotFound(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/liens/999999');

        self::assertResponseStatusCodeSame(404);
    }
}
