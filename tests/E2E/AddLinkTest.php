<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use Playwright\Locator\LocatorInterface;

final class AddLinkTest extends E2ETestCase
{
    public function testMemberAddsLinkFromTopbarModal(): void
    {
        $url = 'https://example.com/e2e-' . uniqid();
        $this->logIn();

        $modal = $this->openModal();
        $this->expect($modal->locator('h1'))->toContainText('Ajouter un lien');

        $modal->locator('[data-test="link-url"]')->fill($url);
        $modal->locator('[data-test="link-categories"] > div > label')->first()->click();
        $modal->locator('[data-test="link-context"]')->fill('Repéré en E2E.');
        $modal->locator('[data-test="link-submit"]')->click();

        $this->expect($modal)->toBeHidden();
        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/');
        $this->expect($this->page->locator('[role="alert"]'))->toContainText('Lien ajouté.');
        $first = $this->page->locator('[data-test="link-card"]')->first();
        $this->expect($first->locator('[data-test="link-context"]'))->toHaveText('Repéré en E2E.');
        $this->expect($first->locator('[data-test="link-source"]'))->toContainText('example.com');
    }

    public function testDuplicateIsShownAsSoonAsTheUrlIsTyped(): void
    {
        $slug = 'e2e-dup-' . uniqid();
        $this->logIn();
        $modal = $this->openModal();
        $modal->locator('[data-test="link-url"]')->fill('https://example.com/' . $slug);
        $modal->locator('[data-test="link-categories"] > div > label')->first()->click();
        $modal->locator('[data-test="link-submit"]')->click();
        $this->expect($modal)->toBeHidden();

        $modal = $this->openModal();
        // Même lien une fois normalisé (www, slash final)
        $modal->locator('[data-test="link-url"]')->fill('https://www.example.com/' . $slug . '/');

        $this->expect($modal->locator('[data-test="link-duplicate"]'))->toContainText('Ce lien est déjà dans Radar');
        $this->expect($modal->locator('[data-test="link-submit"]'))->toBeDisabled();
    }

    public function testThirdCategoryIsLockedOnceTwoAreChecked(): void
    {
        $this->logIn();
        $modal = $this->openModal();

        $labels = $modal->locator('[data-test="link-categories"] > div > label');
        $labels->nth(0)->click();
        $labels->nth(1)->click();

        $this->expect($modal->locator('[data-test="link-categories"] input')->nth(2))->toBeDisabled();
    }

    public function testCancelClosesTheModalAndStartsAFreshFormNextTime(): void
    {
        $this->logIn();
        $modal = $this->openModal();
        $modal->locator('[data-test="link-url"]')->fill('https://brouillon.example');

        $modal->locator('[data-test="link-cancel"]')->click();
        $this->expect($modal)->toBeHidden();

        $modal = $this->openModal();
        $this->expect($modal->locator('[data-test="link-url"]'))->toHaveValue('');
    }

    private function openModal(): LocatorInterface
    {
        $this->page->click('[data-test="add-link"]');
        $modal = $this->page->locator('[data-test="link-modal"]');
        $this->expect($modal->locator('[data-test="link-url"]'))->toBeVisible();

        return $modal;
    }
}
