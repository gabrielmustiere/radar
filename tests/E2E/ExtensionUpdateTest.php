<?php

declare(strict_types=1);

namespace App\Tests\E2E;

final class ExtensionUpdateTest extends ExtensionTestCase
{
    public function testOutdatedExtensionIsWarnedAndStillAddsALink(): void
    {
        $this->installVersion('0.0.1');
        $this->openFormWithAValidKey();

        $update = $this->page->locator('[data-test="extension-update"]');
        $this->expect($update)->toContainText('Nouvelle version disponible');
        $this->expect($update->locator('a'))->toHaveAttribute('href', 'https://radar.wip/extension');

        $this->page->locator('[data-test="extension-category"]')->first()->click();
        $this->page->locator('[data-test="extension-submit"]')->click();
        $this->expect($this->page->locator('[data-test="extension-done"]'))->toBeVisible();
    }

    public function testUpToDateExtensionIsNotWarned(): void
    {
        $this->installVersion(self::availableVersion());
        $this->openFormWithAValidKey();

        $this->expectNoWarningOnceTheCheckIsDone();
    }

    public function testExtensionAheadOfRadarIsNotWarned(): void
    {
        $this->installVersion('999.0.0');
        $this->openFormWithAValidKey();

        $this->expectNoWarningOnceTheCheckIsDone();
    }

    public function testFailedCheckLeavesTheFormUsable(): void
    {
        $this->installVersion('0.0.1');
        $this->context->addInitScript(<<<'JS'
            const realFetch = window.fetch;
            window.fetch = (url, options) => String(url).endsWith('/version')
                ? Promise.reject(new Error('Radar injoignable'))
                : realFetch(url, options);
            JS);
        $this->openFormWithAValidKey();

        $this->expectNoWarningOnceTheCheckIsDone();
        $this->expect($this->page->locator('[data-test="extension-message"]'))->toBeHidden();

        $this->page->locator('[data-test="extension-category"]')->first()->click();
        $this->page->locator('[data-test="extension-submit"]')->click();
        $this->expect($this->page->locator('[data-test="extension-done"]'))->toBeVisible();
    }

    private function openFormWithAValidKey(): void
    {
        $this->logIn();
        $key = $this->generateKey();
        $this->openPopup('https://example.com/e2e-extension-' . uniqid());
        $this->pasteKey($key);
        $this->expect($this->page->locator('[data-test="extension-link-form"]'))->toBeVisible();
    }

    private function expectNoWarningOnceTheCheckIsDone(): void
    {
        $this->page->evaluate('new Promise((resolve) => setTimeout(resolve, 500))');
        $this->expect($this->page->locator('[data-test="extension-update"]'))->toBeHidden();
    }

    private static function availableVersion(): string
    {
        /** @var array{version: string} $manifest */
        $manifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, levels: 2) . '/extension/manifest.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        return $manifest['version'];
    }
}
