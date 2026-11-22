<?php

declare(strict_types=1);

namespace App\Tests\E2E;

/** App installable : manifeste, icônes, service worker et page hors ligne, tels que le navigateur les voit. */
final class PwaTest extends E2ETestCase
{
    public function testManifestIsLinkedAndDeclaresTheShareTarget(): void
    {
        $this->logIn();

        /** @var array{display: string, start_url: string, share_target: array{action: string, method: string}, icons: list<array{src: string}>} $manifest */
        $manifest = $this->page->evaluate(
            "fetch(document.querySelector('link[rel=manifest]').href, { credentials: 'omit' }).then((response) => response.json())",
        );

        self::assertSame('standalone', $manifest['display']);
        self::assertSame('/', $manifest['start_url']);
        self::assertSame('/liens/ajouter', $manifest['share_target']['action']);
        self::assertSame('GET', $manifest['share_target']['method']);
        foreach ($manifest['icons'] as $icon) {
            self::assertSame(200, $this->page->evaluate(
                "(src) => fetch(src, { credentials: 'omit' }).then((response) => response.status)",
                $icon['src'],
            ));
        }
    }

    public function testInstalledAppShowsTheOfflinePageWithoutNetwork(): void
    {
        $this->logIn();
        self::assertSame(
            'activated',
            $this->page->evaluate('navigator.serviceWorker.ready.then((registration) => registration.active.state)'),
        );
        // Le service worker ne contrôle que les pages chargées après son activation.
        $this->page->reload();

        $this->context->setOffline(true);
        $this->page->goto('/');

        $this->expect($this->page->locator('[data-test="offline-page"]'))->toBeVisible();
        $this->expect($this->page->locator('[data-test="link-card"]'))->toHaveCount(0);
        $this->context->setOffline(false);
    }

    public function testMenuEntryLeadsToTheHelpPageWithoutNativePrompt(): void
    {
        $this->logIn();

        $this->page->click('[data-test="user-menu-toggle"]');
        $this->page->click('[data-test="install-app-link"]');

        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/installer');
        $this->expect($this->page->locator('[data-test="install-iphone"]'))->toBeVisible();
    }

    public function testMenuEntryIsHiddenInTheInstalledApp(): void
    {
        $this->context->addInitScript(<<<'JS'
            const matchMedia = window.matchMedia.bind(window);
            window.matchMedia = (query) => query === '(display-mode: standalone)'
                ? { ...matchMedia(query), matches: true, media: query }
                : matchMedia(query);
            JS);
        $this->logIn();

        $this->page->click('[data-test="user-menu-toggle"]');

        $this->expect($this->page->locator('[data-test="extension-key-link"]'))->toBeVisible();
        $this->expect($this->page->locator('[data-test="install-app-link"]'))->toBeHidden();
    }

    public function testSharedLinkKeepsItsChannelThroughTheFormUntilAdded(): void
    {
        $this->logIn();
        $url = $this->freeRealLink();

        $this->page->goto('/liens/ajouter?' . http_build_query(['title' => 'Partage', 'text' => 'À lire ' . $url]));
        $this->expect($this->page->locator('[data-test="link-url"]'))->toHaveValue($url);

        // Cocher une catégorie redessine le formulaire côté serveur (Live Component) : le canal doit y survivre.
        $this->page->locator('[data-test="link-categories"] > div > label')->first()->click();
        $this->page->waitForLoadState('networkidle');
        $this->expect($this->page->locator('[data-test="link-channel"]'))->toHaveValue('share');

        $this->page->locator('[data-test="link-submit"]')->click();

        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/');
        $this->expect($this->page->locator('[role="alert"]'))->toContainText('Lien ajouté.');
    }
}
