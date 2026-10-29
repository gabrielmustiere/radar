<?php

declare(strict_types=1);

namespace App\Tests\E2E;

final class SearchTest extends E2ETestCase
{
    public function testTypingFiltersTheFeedWithoutReloading(): void
    {
        $this->logIn();
        $this->page->evaluate('window.notReloaded = true');

        $this->page->locator('[data-test="topbar-search"]')->pressSequentially('symfony', ['delay' => 30]);

        $this->expect($this->page->locator('[data-test="link-count"]'))->toContainText('pour « symfony »');
        $this->expect($this->page->locator('[data-test="link-card"]')->first())->toContainText('symfony');
        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/?q=symfony');
        $this->expect($this->page->locator('[data-test="topbar-search"]'))->toBeFocused();
        self::assertTrue($this->page->evaluate('window.notReloaded === true'));

        $this->page->locator('[data-test="topbar-search"]')->fill('introuvable' . uniqid());
        $this->expect($this->page->locator('[data-test="search-empty"]'))->toBeVisible();
    }

    public function testCardsStillMatchingAreKeptInsteadOfRedrawn(): void
    {
        $this->logIn();
        $search = $this->page->locator('[data-test="topbar-search"]');

        $search->fill('symf');
        $this->expect($this->page->locator('[data-test="link-count"]'))->toContainText('pour « symf »');
        // Propriété JS et non attribut : le morphing aligne les attributs sur la réponse, mais garde le nœud
        $this->page->evaluate(
            'document.querySelectorAll(\'[data-test="link-card"]\').forEach((card) => card.kept = true)',
        );
        $search->fill('symfony');
        $this->expect($this->page->locator('[data-test="link-count"]'))->toContainText('pour « symfony »');

        self::assertTrue($this->page->evaluate('document.querySelector(\'[data-test="link-card"]\').kept === true'));
    }

    public function testTypingFiltersTheCurrentCategory(): void
    {
        $this->logIn();
        $item = $this->page->locator('[data-test="category-nav-item"]')->first();
        $name = trim((string) $item->locator('span.truncate')->textContent());
        $item->click();
        $this->expect($this->page->locator('h1'))->toHaveText($name);
        $categoryUrl = $this->page->url();

        $this->page->locator('[data-test="topbar-search"]')->fill('introuvable');

        $this->expect($this->page->locator('[data-test="search-empty"]'))->toBeVisible();
        $this->expect($this->page->locator('h1'))->toHaveText($name);
        $this->expect($this->page)->toHaveURL($categoryUrl . '?q=introuvable');
    }

    public function testSearchingOutsideAListLeadsToTheFilteredFeed(): void
    {
        $this->logIn();
        $this->page->goto('/design-system');

        $this->page->locator('[data-test="topbar-search"]')->fill('symfony');
        $this->page->locator('[data-test="topbar-search"]')->press('Enter');

        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/?q=symfony');
        $this->expect($this->page->locator('[data-test="link-count"]'))->toContainText('pour « symfony »');
        $this->expect($this->page->locator('[data-test="topbar-search"]'))->toHaveValue('symfony');
    }

    public function testSearchIsAvailableOnMobile(): void
    {
        $this->page->setViewportSize(375, 812);
        $this->logIn();

        $this->page->locator('[data-test="topbar-search"]')->fill('symfony');

        $this->expect($this->page->locator('[data-test="link-count"]'))->toContainText('pour « symfony »');
    }

    public function testSlashFocusesTheTopbarSearch(): void
    {
        $this->logIn();

        $this->page->keyboard()->press('/');

        $this->expect($this->page->locator('[data-test="topbar-search"]'))->toBeFocused();
    }
}
