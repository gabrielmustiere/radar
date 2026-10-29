<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;

final class LinkSearchTest extends LinkWebTestCase
{
    /** @param array<string, string> $values */
    #[DataProvider('searchableFields')]
    public function testFindsLinkByEachSearchableField(string $search, array $values): void
    {
        $client = $this->loggedClient();
        $marker = uniqid('mk');
        $this->submit($client, [
            'url' => 'https://example.com/' . $marker,
            'categories' => [$this->categoryId('Tester une app mobile')],
            ...array_map(static fn(string $value): string => str_replace('{mk}', $marker, $value), $values),
        ]);

        $client->request('GET', '/', ['q' => str_replace('{mk}', $marker, $search)]);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, '[data-test="link-card"]');
        self::assertSelectorTextContains(
            '[data-test="link-count"]',
            '1 lien pour « ' . str_replace('{mk}', $marker, $search) . ' »',
        );
    }

    /** @return \Generator<string, array{string, array<string, string>}> */
    public static function searchableFields(): \Generator
    {
        yield 'adresse' => ['{mk}', []];
        yield 'contexte' => ['ctx{mk}', ['context' => 'Très utile : ctx{mk} pour la recette.']];
        yield 'catégorie' => ['cat{mk}', ['newCategory' => 'Besoin cat{mk}']];
        yield 'casse ignorée' => ['CTX{mk}', ['context' => 'ctx{mk}']];
    }

    public function testFindsLinkByPreviewTitleAndDescription(): void
    {
        $client = $this->loggedClient();
        $marker = uniqid('art');
        $this->submit($client, [
            'url' => 'https://example.com/article-' . $marker,
            'categories' => [$this->categoryId('Tester une app mobile')],
        ]);

        $client->request('GET', '/', ['q' => $marker . ' article savoir']);
        self::assertSelectorCount(1, '[data-test="link-card"]');
    }

    public function testAllWordsMustMatch(): void
    {
        $client = $this->loggedClient();
        $marker = uniqid('et');
        $this->submit($client, [
            'url' => 'https://example.com/' . $marker . '-a',
            'categories' => [$this->categoryId('Tester une app mobile')],
            'context' => 'alpha',
        ]);
        $this->submit($client, [
            'url' => 'https://example.com/' . $marker . '-b',
            'categories' => [$this->categoryId('Tester une app mobile')],
            'context' => 'beta',
        ]);

        $client->request('GET', '/', ['q' => $marker . ' beta']);

        self::assertSelectorCount(1, '[data-test="link-card"]');
        self::assertSelectorTextContains('[data-test="link-card"]', $marker . '-b');
    }

    public function testNoResultIsExplicitAndQueryIsKept(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/', ['q' => 'introuvable' . uniqid()]);

        self::assertSelectorTextContains('[data-test="search-empty"]', 'Aucun lien ne contient tous ces mots.');
        self::assertInputValueSame('q', $client->getRequest()->query->getString('q'));
    }

    public function testWildcardIsSearchedLiterally(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/', ['q' => '%%']);

        self::assertSelectorExists('[data-test="search-empty"]');
    }

    public function testEmptyQueryListsAllLinks(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/', ['q' => ' a ']);

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('[data-test="search-empty"]');
        self::assertSelectorTextNotContains('[data-test="link-count"]', 'pour');
    }

    public function testCategorySearchStaysWithinTheCategory(): void
    {
        $client = $this->loggedClient();
        $marker = uniqid('cat');
        $name = 'Filtre ' . uniqid();
        $this->submit($client, ['url' => 'https://example.com/' . $marker . '-dedans', 'newCategory' => $name]);
        $this->submit($client, [
            'url' => 'https://example.com/autre-' . uniqid(),
            'categories' => [$this->categoryId($name)],
        ]);
        $this->submit($client, [
            'url' => 'https://example.com/' . $marker . '-dehors',
            'categories' => [$this->categoryId('Tester une app mobile')],
        ]);

        $client->request('GET', '/categories/' . $this->categoryId($name), ['q' => $marker]);

        self::assertSelectorCount(1, '[data-test="link-card"]');
        self::assertSelectorTextContains('[data-test="link-card"]', $marker . '-dedans');
    }

    public function testSearchFormFiltersTheCurrentListOrLeadsToTheFeed(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/categories/' . $this->categoryId('Tester une app mobile'));
        self::assertSelectorExists('form[role="search"][data-controller~="search-filter"]');

        $client->request('GET', '/design-system');
        self::assertSelectorExists('form[role="search"][action="/"]:not([data-controller~="search-filter"])');
    }
}
