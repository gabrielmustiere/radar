<?php

declare(strict_types=1);

namespace App\Tests\E2E;

/**
 * Barre de filtres du fil, sur les liens des fixtures (aucun lien du catalogue E2E consommé).
 */
final class FilterTest extends E2ETestCase
{
    public function testTypeChipFiltersTheListAndSurvivesTheSearch(): void
    {
        $this->logIn();

        $this->page->locator('[data-test="filter-type"][href="/?type=github"]')->click();

        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/?type=github');
        $this->expect($this->page->locator('h1'))->toHaveText('Dépôts GitHub');
        $types = $this->page->locator('[data-test="link-card"] [data-test="link-type"]');
        $this->expect($types->first())->toHaveText('Dépôt GitHub');
        self::assertSame(['Dépôt GitHub'], $this->page->evaluate(<<<'JS'
            () => [...new Set([...document.querySelectorAll('[data-test="link-card"] [data-test="link-type"]')].map((type) => type.textContent.trim()))]
            JS));

        $this->page->locator('[data-test="topbar-search"]')->fill('anthropics');
        $this->expect($this->page->locator('[data-test="link-count"]'))->toContainText('pour « anthropics »');
        $this->expect($this->page->locator('h1'))->toHaveText('Dépôts GitHub');
        self::assertSame(
            ['anthropics', 'github'],
            $this->page->evaluate("['q', 'type'].map((name) => new URL(location.href).searchParams.get(name))"),
        );

        $this->page->locator('[data-test="filters-reset"]')->click();
        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/');
        $this->expect($this->page->locator('h1'))->toHaveText('Tous les liens');
    }

    public function testPhoneUsesTheFloatingAddButton(): void
    {
        $this->logIn();
        $this->page->setViewportSize(390, 844);
        $this->page->goto('/');

        $this->expect($this->page->locator('[data-test="add-link"]'))->toBeHidden();
        $this->page->locator('[data-test="add-link-fab"]')->click();

        $this->expect($this->page->locator('[data-test="link-modal"] [data-test="link-url"]'))->toBeVisible();
    }
}
