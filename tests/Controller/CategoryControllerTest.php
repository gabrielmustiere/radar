<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Component\DomCrawler\Crawler;

final class CategoryControllerTest extends LinkWebTestCase
{
    public function testFilterBarListsCategoriesWithLinkCount(): void
    {
        $client = $this->loggedClient();
        $name = 'Filtre ' . uniqid();
        $categories = [$this->createCategory($name)];
        $this->submit($client, ['url' => 'https://example.com/' . uniqid('nav-a-'), 'categories' => $categories]);
        $this->submit($client, ['url' => 'https://example.com/' . uniqid('nav-b-'), 'categories' => $categories]);

        $crawler = $client->request('GET', '/');

        $chip = $this->categoryChip($crawler, $name);
        self::assertCount(1, $chip);
        self::assertSame('2', $chip->filter('[data-test="filter-count"]')->text());
        self::assertSame('/categories/' . $this->categoryId($name), $chip->attr('href'));
    }

    public function testFilterBarSortsCategoriesIgnoringCaseAndAccentsWithNaturalNumbers(): void
    {
        $client = $this->loggedClient();
        $prefix = 'Tri ' . uniqid() . ' ';
        $expected = [$prefix . '2', $prefix . '10', $prefix . 'éclair', $prefix . 'zèbre', $prefix . 'Zinc'];
        foreach ([$prefix . 'Zinc', $prefix . '10', $prefix . 'zèbre', $prefix . 'éclair', $prefix . '2'] as $name) {
            $this->submit($client, [
                'url' => 'https://example.com/' . uniqid('tri-'),
                'categories' => [$this->createCategory($name)],
            ]);
        }

        $crawler = $client->request('GET', '/');

        $names = $crawler
            ->filter('[data-test="filter-category"] [data-test="filter-label"]')
            ->reduce(static fn(Crawler $node): bool => str_starts_with($node->text(), $prefix))
            ->each(static fn(Crawler $node): string => $node->text());
        self::assertSame($expected, $names);
    }

    public function testCategoryWithoutLinkIsGreyedOutInTheFilterBar(): void
    {
        $client = $this->loggedClient();
        $name = 'Sans lien ' . uniqid();
        $this->createCategory($name);

        $crawler = $client->request('GET', '/');

        $chip = $this->categoryChip($crawler, $name);
        self::assertSame('0', $chip->filter('[data-test="filter-count"]')->text());
        self::assertStringContainsString('opacity-40', (string) $chip->attr('class'));
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
        $crawler = $client->getCrawler();
        self::assertSame(
            'Ouvrir ' . $newer,
            $crawler->filter('[data-test="link-list"] > :nth-child(1) [data-test="link-open"]')->attr('title'),
        );
        self::assertSame(
            'Ouvrir ' . $older,
            $crawler->filter('[data-test="link-list"] > :nth-child(2) [data-test="link-open"]')->attr('title'),
        );
        self::assertSelectorCount(2, '[data-test="link-list"] > :nth-child(1) [data-test="link-category"]');
        self::assertSelectorTextContains('[data-test="filter-category"][aria-current="true"]', $name);
    }

    public function testCardOpensOriginalLinkAndCategoriesAreNotLinks(): void
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
        $category = $crawler->filter('[data-test="link-category"]');
        self::assertSame($name, trim($category->text()));
        self::assertSame('span', $category->nodeName());
    }

    public function testOnlyAdminSeesTheRenameButtonOnACategoryPage(): void
    {
        $client = $this->loggedClient();
        $path = '/categories/' . $this->createCategory('Renommable ' . uniqid());

        $client->request('GET', $path);
        self::assertSelectorExists('[data-test="category-rename"]');
        self::assertSelectorExists('#modal-category-rename [data-test="category-rename-name"]');

        $client->request('GET', '/');
        self::assertSelectorNotExists('[data-test="category-rename"]');

        $repository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);
        $member = $repository->findOneByEmail('marie@example.com');
        self::assertNotNull($member);
        $client->loginUser($member);
        $client->request('GET', $path);
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('[data-test="category-rename"]');
    }

    public function testUnknownCategoryIsNotFound(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/categories/999999');

        self::assertResponseStatusCodeSame(404);
    }

    private function categoryChip(Crawler $crawler, string $name): Crawler
    {
        return $crawler
            ->filter('[data-test="filter-category"]')
            ->reduce(static fn(Crawler $node): bool => $node->filter('[data-test="filter-label"]')->text() === $name);
    }
}
