<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use PHPUnit\Framework\TestCase;
use Playwright\Configuration\PlaywrightConfigBuilder;
use Playwright\Testing\PlaywrightTestCaseTrait;

/**
 * Tests E2E sur le serveur lancé (`make serve`), base URL surchargeable via PLAYWRIGHT_BASE_URL.
 */
abstract class E2ETestCase extends TestCase
{
    use PlaywrightTestCaseTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        // Config lue depuis l'environnement (PW_HEADLESS=0 pour le mode visible).
        $this->setUpPlaywright(customConfig: PlaywrightConfigBuilder::fromEnv()->build());

        // Le trait crée un contexte sans options : on le remplace pour la base URL et le certificat local.
        $this->context->close();
        $this->context = $this->browser->newContext([
            'baseURL' => self::baseUrl(),
            'ignoreHTTPSErrors' => true,
        ]);
        $this->page = $this->context->newPage();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->tearDownPlaywright();
        parent::tearDown();
    }

    protected static function baseUrl(): string
    {
        $baseUrl = getenv('PLAYWRIGHT_BASE_URL');

        return false === $baseUrl || '' === $baseUrl ? 'https://radar.wip' : rtrim($baseUrl, characters: '/');
    }

    protected function logIn(): void
    {
        $this->page->goto('/login');
        $this->page->locator('input[name="_username"]')->fill('admin@example.com');
        $this->page->locator('input[name="_password"]')->fill('password');
        $this->page->click('button[type="submit"]');
        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/');
    }
}
