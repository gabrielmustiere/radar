<?php

declare(strict_types=1);

namespace App\Tests\E2E;

final class ExploreCategoryTest extends E2ETestCase
{
    public function testMemberBrowsesCategoryAndOpensOriginalLink(): void
    {
        $this->logIn();

        $item = $this->page->locator('[data-test="category-nav-item"]')->first();
        $name = trim((string) $item->locator('span.truncate')->textContent());
        $item->click();

        $this->expect($this->page->locator('h1'))->toHaveText($name);
        $this->expect($this->page->locator('[data-test="category-nav-item"][aria-current="page"]'))->toContainText(
            $name,
        );

        $open = $this->page->locator('[data-test="link-card"]')->first()->locator('[data-test="link-open"]');
        $destination = substr((string) $open->getAttribute('title'), offset: strlen('Ouvrir '));
        $popup = $this->page->waitForPopup($open->click(...));

        $this->expect($popup)->toHaveURL($destination);
    }

    public function testAdminRenamesTheCategoryFromItsPage(): void
    {
        $this->logIn();
        $item = $this->page->locator('[data-test="category-nav-item"]')->first();
        $name = trim((string) $item->locator('span.truncate')->textContent());
        $item->click();
        $this->expect($this->page->locator('h1'))->toHaveText($name);

        $this->rename($name . ' renommée');
        $this->expect($this->page->locator('h1'))->toHaveText($name . ' renommée');
        $this->expect($this->page->locator('[data-test="category-nav-item"][aria-current="page"]'))->toContainText($name
        . ' renommée');

        // Retour au nom d'origine : la base de dev reste stable d'un passage à l'autre.
        $this->rename($name);
        $this->expect($this->page->locator('h1'))->toHaveText($name);
    }

    private function rename(string $name): void
    {
        $this->page->locator('[data-test="category-rename"]')->click();
        $modal = $this->page->locator('#modal-category-rename');
        $this->expect($modal)->toBeVisible();
        $modal->locator('[data-test="category-rename-name"]')->fill($name);
        $modal->locator('[data-test="category-rename-submit"]')->click();
        $this->expect($modal)->toBeHidden();
    }
}
