<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Component\DomCrawler\Crawler;

final class CategoryControllerTest extends LinkWebTestCase
{
    public function testSidebarListsCategoriesWithLinkCount(): void
    {
        $client = $this->loggedClient();
        $name = 'Sidebar ' . uniqid();
        $categories = [$this->createCategory($name)];
        $this->submit($client, ['url' => 'https://example.com/' . uniqid('nav-a-'), 'categories' => $categories]);
        $this->submit($client, ['url' => 'https://example.com/' . uniqid('nav-b-'), 'categories' => $categories]);

        $crawler = $client->request('GET', '/');

        $item = $crawler
            ->filter('[data-test="category-nav-item"]')
            ->reduce(static fn(Crawler $node): bool => str_contains($node->text(), $name));
        self::assertCount(1, $item);
        self::assertStringEndsWith('2', trim($item->text()));
    }

    public function testCategoryWithoutLinkIsNotInTheSidebar(): void
    {
        $client = $this->loggedClient();
        $name = 'Sans lien ' . uniqid();
        $this->createCategory($name);

        $client->request('GET', '/');

        self::assertSelectorTextNotContains('[data-test="category-nav"]', $name);
        $client->request('GET', '/liens/ajouter');
        self::assertSelectorTextContains('[data-test="link-categories"]', $name);
    }

    public function testCategoryPageListsOnlyItsLinksMostRecentFirst(): void
    {
        $client = $this->loggedClient();
        $name = 'Besoin ' . uniqid();
        $other = 'Voisin ' . uniqid();
        $older = 'https://example.com/' . uniqid('ancien-');
        $newer = 'https://example.com/' . uniqid('recent-');
        $outside = 'https://example.com/' . uniqid('ailleurs-');
        $nameId = $this->createCategory($name);
        $otherId = $this->createCategory($other);
        $this->submit($client, ['url' => $older, 'categories' => [$nameId]]);
        $this->submit($client, ['url' => $newer, 'categories' => [$nameId, $otherId]]);
        $this->submit($client, ['url' => $outside, 'categories' => [$otherId]]);

        $client->request('GET', '/categories/' . $this->categoryId($name));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $name);
        self::assertSelectorCount(2, '[data-test="link-card"]');
        self::assertSelectorTextContains('[data-test="link-list"] > :nth-child(1) [data-test="link-title"]', $newer);
        self::assertSelectorTextContains('[data-test="link-list"] > :nth-child(2) [data-test="link-title"]', $older);
        self::assertSelectorCount(2, '[data-test="link-list"] > :nth-child(1) [data-test="link-category"]');
        self::assertSelectorTextContains('[data-test="category-nav-item"][aria-current="page"]', $name);
    }

    public function testCardOpensOriginalLinkAndLinksToCategories(): void
    {
        $client = $this->loggedClient();
        $name = 'Rebond ' . uniqid();
        $url = 'https://example.com/' . uniqid('ouvrir-');
        $this->submit($client, ['url' => $url, 'categories' => [$this->createCategory($name)]]);

        $crawler = $client->request('GET', '/categories/' . $this->categoryId($name));

        $open = $crawler->filter('[data-test="link-open"]');
        self::assertMatchesRegularExpression('#^/liens/\d+/ouvrir$#', (string) $open->attr('href'));
        self::assertSame('Ouvrir ' . $url, $open->attr('title'));
        self::assertSame('_blank', $open->attr('target'));
        self::assertSame('noopener noreferrer', $open->attr('rel'));
        self::assertSame($open->attr('href'), $crawler->filter('[data-test="link-title"] a')->attr('href'));
        $client->click($crawler->filter('[data-test="link-category"]')->link());
        self::assertSelectorTextContains('h1', $name);
    }

    public function testUnknownCategoryIsNotFound(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/categories/999999');

        self::assertResponseStatusCodeSame(404);
    }
}
