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

    public function testSlashFocusesTheTopbarSearch(): void
    {
        $this->logIn();

        $this->page->keyboard()->press('/');

        $this->expect($this->page->locator('[data-test="topbar-search"]'))->toBeFocused();
    }
}
