<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Enum\Type\Team;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/** Barre de filtres du fil en entonnoir : Équipe › Catégorie › Type, avec la recherche. */
final class LinkFilterTest extends LinkWebTestCase
{
    public function testTeamKeepsLinksOfItsCategoriesAndOnlyListsItsCategories(): void
    {
        $client = $this->loggedClient();
        $run = uniqid();
        $name = 'Équipe produit ' . $run;
        $this->submit($client, [
            'url' => 'https://example.com/' . $run,
            'categories' => [$this->teamCategory($name, Team::Product)],
        ]);

        $crawler = $client->request('GET', '/?team=produit&q=' . $run);
        self::assertSelectorCount(1, '[data-test="link-card"]');
        self::assertContains($name, $this->labels($crawler, 'filter-category'));
        self::assertSelectorTextSame(
            '[data-test="filter-team"][aria-current="true"] [data-test="filter-label"]',
            'Produit',
        );

        $crawler = $client->request('GET', '/?team=qa&q=' . $run);
        self::assertSelectorNotExists('[data-test="link-card"]');
        self::assertNotContains($name, $this->labels($crawler, 'filter-category'));
    }

    public function testTypeFiltersTheListAndEachOptionCountsWithTheOtherFilters(): void
    {
        $client = $this->loggedClient();
        $run = uniqid();
        $id = $this->createCategory('Types ' . $run);
        $this->submit($client, ['url' => 'https://github.com/acme/' . $run, 'categories' => [$id]]);
        $this->submit($client, ['url' => 'https://example.com/' . $run, 'categories' => [$id]]);

        $crawler = $client->request('GET', sprintf('/categories/%d?type=github', $id));

        self::assertSelectorCount(1, '[data-test="link-card"]');
        self::assertSelectorTextSame('[data-test="link-card"] [data-test="link-type"]', 'Dépôt GitHub');
        self::assertSame(
            '2',
            $this->chip($crawler, 'filter-type', 'Tous')->filter('[data-test="filter-count"]')->text(),
        );
        self::assertSame(
            '1',
            $this->chip($crawler, 'filter-type', 'Dépôts GitHub')->filter('[data-test="filter-count"]')->text(),
        );
        self::assertSame(
            '1',
            $this->chip($crawler, 'filter-type', 'Articles')->filter('[data-test="filter-count"]')->text(),
        );
        self::assertSame(
            '0',
            $this->chip($crawler, 'filter-type', 'Vidéos')->filter('[data-test="filter-count"]')->text(),
        );
        self::assertSame(
            '1',
            $this
                ->chip($crawler, 'filter-category', 'Types ' . $run)
                ->filter('[data-test="filter-count"]')
                ->text(),
        );
    }

    public function testFollowedSourcesTypeKeepsOnlyLinksPublishedBySources(): void
    {
        $client = $this->loggedClient();

        $crawler = $client->request('GET', '/?type=rss');

        self::assertGreaterThan(0, $crawler->filter('[data-test="link-card"]')->count());
        self::assertSame(['Source suivie'], array_values(array_unique($crawler
            ->filter('[data-test="link-type"]')
            ->each(static fn(Crawler $node): string => $node->text()))));
    }

    public function testTitleFollowsTheFunnelAndResetAppearsOnceFiltered(): void
    {
        $client = $this->loggedClient();
        $name = 'Chemin ' . uniqid();
        $category = $this->teamCategory($name, Team::Product);

        $client->request('GET', sprintf('/categories/%d?team=produit&type=github', $category));
        self::assertSelectorTextSame('h1', 'Produit › ' . $name . ' › Dépôts GitHub');
        self::assertSelectorExists('[data-test="filters-reset"]');
        self::assertSelectorExists('[data-test="filter-empty"]');

        $client->request('GET', '/');
        self::assertSelectorTextSame('h1', 'Tous les liens');
        self::assertSelectorNotExists('[data-test="filters-reset"]');
    }

    public function testChoosingAnotherTeamLeavesACategoryOfAnotherTeam(): void
    {
        $client = $this->loggedClient();
        $category = $this->teamCategory('Bascule ' . uniqid(), Team::Product);

        $crawler = $client->request('GET', sprintf('/categories/%d?type=github', $category));

        self::assertSame(
            sprintf('/categories/%d?type=github&team=produit', $category),
            $this->chip($crawler, 'filter-team', 'Produit')->attr('href'),
        );
        self::assertSame('/?type=github&team=qa', $this->chip($crawler, 'filter-team', 'QA')->attr('href'));
    }

    public function testUnknownFilterValueIsNotFound(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/?type=podcast');

        self::assertResponseStatusCodeSame(404);
    }

    private function teamCategory(string $name, Team $team): int
    {
        $id = $this->createCategory($name);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $category = $entityManager->find(Category::class, $id);
        self::assertNotNull($category);
        $category->setTeam($team);
        $entityManager->flush();

        return $id;
    }

    /** @return list<string> */
    private function labels(Crawler $crawler, string $row): array
    {
        return $crawler
            ->filter(sprintf('[data-test="%s"] [data-test="filter-label"]', $row))
            ->each(static fn(Crawler $node): string => $node->text());
    }

    private function chip(Crawler $crawler, string $row, string $label): Crawler
    {
        $chip = $crawler
            ->filter(sprintf('[data-test="%s"]', $row))
            ->reduce(static fn(Crawler $node): bool => $node->filter('[data-test="filter-label"]')->text() === $label);
        self::assertCount(1, $chip);

        return $chip;
    }
}
