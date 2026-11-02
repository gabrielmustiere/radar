<?php

declare(strict_types=1);

namespace App\Tests\E2E;

final class ExtensionTest extends ExtensionTestCase
{
    public function testMemberAddsTheCurrentPageWithANewCategory(): void
    {
        $this->logIn();
        $key = $this->generateKey();
        $url = 'https://example.com/e2e-extension-' . uniqid();
        $category = 'E2E extension ' . uniqid();

        $this->openPopup($url);
        $this->pasteKey($key);

        $this->expect($this->page->locator('[data-test="extension-url"]'))->toHaveValue($url);
        $this->page->locator('[data-test="extension-context"]')->fill('Repéré depuis l\'extension.');
        $this->page->locator('[data-test="extension-category"]')->first()->click();
        $query = $this->page->locator('[data-test="extension-category-query"]');
        $query->fill($category);
        $query->press('Enter');
        $this->expect($this->page->locator(sprintf(
            '[data-test="extension-category"][aria-pressed="true"]:has-text("%s")',
            $category,
        )))->toBeVisible();
        $this->page->locator('[data-test="extension-submit"]')->click();

        $this->expect($this->page->locator('[data-test="extension-done"]'))->toBeVisible();
        $this->expect($this->page->locator('[data-test="extension-done"]'))->toContainText('Lien ajouté.');
        $cardUrl = (string) $this->page->locator('[data-test="extension-card-link"]')->getAttribute('href');
        self::assertMatchesRegularExpression('#^https://radar\.wip/liens/\d+$#', $cardUrl);

        $this->page->goto($cardUrl);
        $this->expect($this->page->locator('[data-test="link-context"]')->first())->toHaveText(
            'Repéré depuis l\'extension.',
        );
        $this->expect($this->page->locator('body'))->toContainText($category);
    }

    public function testExistingLinkIsCompletedFromThePopup(): void
    {
        $this->logIn();
        $key = $this->generateKey();
        $url = 'https://example.com/e2e-extension-' . uniqid();
        $this->openPopup($url);
        $this->pasteKey($key);
        $this->page->locator('[data-test="extension-category"]')->nth(0)->click();
        $this->page->locator('[data-test="extension-submit"]')->click();
        $this->expect($this->page->locator('[data-test="extension-done"]'))->toBeVisible();

        $this->openPopup($url);

        $this->expect($this->page->locator('[data-test="extension-duplicate"]'))->toContainText(
            'Ce lien est déjà dans Radar',
        );
        $this->expect($this->page->locator('[data-test="extension-submit"]'))->toBeDisabled();
        $this->page->locator('[data-test="extension-category"]')->nth(1)->click();
        $this->page->locator('[data-test="extension-duplicate-complete"]')->click();
        $this->expect($this->page->locator('[data-test="extension-done"]'))->toBeVisible();
        $this->expect($this->page->locator('[data-test="extension-done"]'))->toContainText('Catégories ajoutées.');
    }

    public function testFormErrorsAreShownInThePopup(): void
    {
        $this->logIn();
        $key = $this->generateKey();
        $this->openPopup('https://example.com/e2e-extension-' . uniqid());
        $this->pasteKey($key);
        $this->expect($this->page->locator('[data-test="extension-link-form"]'))->toBeVisible();

        $this->page->locator('[data-test="extension-submit"]')->click();

        $this->expect($this->page->locator('[data-test="extension-errors"]'))->toContainText(
            'Choisissez au moins une catégorie',
        );
    }

    public function testCategoryCreatedBeyondThreeIsNotSelected(): void
    {
        $this->logIn();
        $key = $this->generateKey();
        $this->openPopup('https://example.com/e2e-extension-' . uniqid());
        $this->pasteKey($key);
        $pills = $this->page->locator('[data-test="extension-category"]');
        $pills->nth(0)->click();
        $pills->nth(1)->click();
        $pills->nth(2)->click();
        $this->expect($pills->nth(3))->toBeDisabled();

        $query = $this->page->locator('[data-test="extension-category-query"]');
        $query->fill('E2E quatrième ' . uniqid());
        $query->press('Enter');

        $this->expect($this->page->locator('[data-test="extension-category-error"]'))->toContainText(
            'désélectionnez une catégorie',
        );
        $this->expect($this->page->locator('[data-test="extension-category"][aria-pressed="true"]'))->toHaveCount(3);
    }

    public function testRevokedKeyIsExplained(): void
    {
        $this->logIn();
        $key = $this->generateKey();
        $this->page->locator('[data-test="extension-key-revoke"]')->click();
        $this->expect($this->page->locator('[data-test="extension-key-none"]'))->toBeVisible();

        $this->openPopup('https://example.com/');
        $this->pasteKey($key);

        $this->expect($this->page->locator('[data-test="extension-invalid-key"]'))->toBeVisible();
        $this->expect($this->page->locator('[data-test="extension-invalid-key"]'))->toContainText('n\'est plus valide');
        $this->expect($this->page->locator('[data-test="extension-key-page"]'))->toHaveAttribute(
            'href',
            'https://radar.wip/extension',
        );
    }

    private function generateKey(): string
    {
        $this->page->goto('/extension');
        $this->page->locator('[data-test="extension-key-generate"]')->click();
        $value = $this->page->locator('[data-test="extension-key-value"]');
        $this->expect($value)->toBeVisible();

        return trim((string) $value->textContent());
    }

    private function pasteKey(string $key): void
    {
        $this->page->locator('[data-test="extension-key-input"]')->fill($key);
        $this->page->locator('[data-test="extension-key-save"]')->click();
    }
}
