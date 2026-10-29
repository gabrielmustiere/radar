<?php

declare(strict_types=1);

namespace App\Tests\E2E;

final class SearchTest extends E2ETestCase
{
    public function testMemberSearchesFromTopbar(): void
    {
        $this->logIn();

        $this->page->locator('[data-test="topbar-search"]')->fill('symfony');
        $this->page->locator('[data-test="topbar-search"]')->press('Enter');

        $this->expect($this->page->locator('h1'))->toContainText('Résultats pour « symfony »');
        $this->expect($this->page->locator('[data-test="link-card"]')->first())->toContainText('symfony');
        $this->expect($this->page->locator('[data-test="topbar-search"]'))->toHaveValue('symfony');
    }

    public function testResultsFollowTheTypingAndStayInTheUrl(): void
    {
        $this->logIn();
        $this->page->goto('/recherche');
        $this->expect($this->page->locator('[data-test="search-hint"]'))->toBeVisible();

        $this->page->locator('[data-test="search-input"]')->pressSequentially('symfony', ['delay' => 30]);

        $this->expect($this->page->locator('h1'))->toContainText('Résultats pour « symfony »');
        $this->expect($this->page->locator('[data-test="link-card"]')->first())->toContainText('symfony');
        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/recherche?q=symfony');

        $this->page->locator('[data-test="search-input"]')->fill('introuvable' . uniqid());
        $this->expect($this->page->locator('[data-test="search-empty"]'))->toBeVisible();
    }

    public function testSlashFocusesTheTopbarSearch(): void
    {
        $this->logIn();

        $this->page->keyboard()->press('/');

        $this->expect($this->page->locator('[data-test="topbar-search"]'))->toBeFocused();
    }
}
