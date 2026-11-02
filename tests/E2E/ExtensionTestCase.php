<?php

declare(strict_types=1);

namespace App\Tests\E2E;

/**
 * Playwright PHP n'implémente pas `launchPersistentContext()` et Chrome n'active pas les extensions dans les contextes
 * qu'il crée : la bulle est ouverte depuis `extension/` en `file://` (Chromium est lancé avec `--disable-web-security`,
 * d'où des appels à l'API sans CORS, comme pour une vraie extension), avec une API `chrome` simulée. Le manifest,
 * l'icône et le clic droit relèvent de la recette manuelle.
 */
abstract class ExtensionTestCase extends E2ETestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->context->addInitScript(<<<'JS'
            if (location.protocol === 'file:') {
                const read = (key) => JSON.parse(localStorage.getItem('chrome.storage.' + key) ?? 'null');
                window.chrome = {
                    storage: { local: {
                        get: async (key) => (null === read(key) ? {} : { [key]: read(key) }),
                        set: async (items) => Object.entries(items).forEach(([key, value]) => localStorage.setItem('chrome.storage.' + key, JSON.stringify(value))),
                        remove: async (key) => localStorage.removeItem('chrome.storage.' + key),
                    } },
                };
            }
            JS);
    }

    protected function openPopup(string $url): void
    {
        if ('https://radar.wip' !== self::baseUrl()) {
            self::markTestSkipped('La bulle cible https://radar.wip quand l\'extension n\'est pas publiée.');
        }

        $this->page->goto(sprintf(
            'file://%s/extension/popup.html?%s',
            dirname(__DIR__, levels: 2),
            http_build_query(['url' => $url]),
        ));
    }
}
