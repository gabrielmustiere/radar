<?php

declare(strict_types=1);

namespace App\Tests\Controller;

/** App installable : page d'aide, entrée de menu et balises de tête lues par le navigateur. */
final class InstallControllerTest extends LinkWebTestCase
{
    public function testHelpPageExplainsEachDevice(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/installer');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-test="install-iphone"]');
        self::assertSelectorExists('[data-test="install-android"]');
        self::assertSelectorExists('[data-test="install-desktop"]');
        self::assertSelectorTextContains('[data-test="install-iphone-login"]', 'Se souvenir de moi');
    }

    public function testAnonymousUserIsRedirectedToLogin(): void
    {
        $client = self::createClient();

        $client->request('GET', '/installer');

        self::assertResponseRedirects('/login');
    }

    public function testMenuLeadsToTheHelpPage(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/');

        self::assertSelectorExists(
            '[data-controller="pwa-install"] a[data-test="install-app-link"][href="/installer"]',
        );
    }

    public function testFeedDeclaresTheInstallableApp(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/');

        self::assertInstallableHead();
    }

    public function testLoginPageDeclaresTheInstallableApp(): void
    {
        $client = self::createClient();

        $client->request('GET', '/login');

        self::assertInstallableHead();
    }

    private static function assertInstallableHead(): void
    {
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('link[rel="manifest"][href="/manifest.json"]');
        self::assertSelectorExists('link[rel="apple-touch-icon"][href="/icons/apple-touch-icon.png"]');
        self::assertSelectorCount(2, 'meta[name="theme-color"]');
        self::assertSelectorExists('meta[name="viewport"][content*="viewport-fit=cover"]');
    }
}
