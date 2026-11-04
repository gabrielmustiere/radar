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

        $this->expect($this->page->locator('[data-test="extension-server"]'))->toHaveText('radar.wip');
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

    public function testKeyIsCopiedAndLeftSelected(): void
    {
        $this->context->grantPermissions(['clipboard-read', 'clipboard-write']);
        $this->logIn();
        $key = $this->generateKey();

        $this->page->locator('[data-test="extension-key-copy"]')->click();

        $this->expect($this->page->locator('[data-test="extension-key-copy"]'))->toContainText('Copiée');
        self::assertSame($key, $this->page->evaluate('navigator.clipboard.readText()'));
        self::assertSame($key, $this->page->evaluate('window.getSelection().toString().trim()'));
    }

    public function testRevokedKeyIsExplained(): void
    {
        $this->context->addInitScript(<<<'JS'
            window.versionChecks = 0;
            const realFetch = window.fetch;
            window.fetch = (url, options) => {
                window.versionChecks += String(url).endsWith('/version') ? 1 : 0;

                return realFetch(url, options);
            };
            JS);
        $this->logIn();
        $key = $this->generateKey();
        $this->page->locator('[data-test="extension-key-revoke"]')->click();
        $this->expect($this->page->locator('[data-test="extension-key-none"]'))->toBeVisible();

        $this->openPopup('https://example.com/');
        $this->pasteKey($key);

        $this->expect($this->page->locator('[data-test="extension-invalid-key"]'))->toBeVisible();
        $this->expect($this->page->locator('[data-test="extension-invalid-key"]'))->toContainText('n\'est plus valide');
        self::assertSame(0, $this->page->evaluate('window.versionChecks'));
        $this->expect($this->page->locator('[data-test="extension-key-page"]'))->toHaveAttribute(
            'href',
            'https://radar.wip/extension',
        );
    }
}
