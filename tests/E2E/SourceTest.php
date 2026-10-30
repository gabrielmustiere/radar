<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use Playwright\Locator\LocatorInterface;

/**
 * Le flux Symfony est le vrai, relu sur le réseau : s'il est injoignable, le test est ignoré plutôt qu'en échec.
 * Il est retiré en fin de test, si bien qu'un second passage le réactive.
 */
final class SourceTest extends E2ETestCase
{
    private const string SYMFONY_FEED = 'https://feeds.feedburner.com/symfony/blog';

    public function testAdminFollowsTheSymfonyFeedThenRemovesIt(): void
    {
        $this->logIn();
        $this->page->locator('[data-test="nav-sources"]')->click();
        $this->expect($this->page->locator('h1'))->toContainText('Sources');
        $this->removeSymfonyFeed();

        $this->page->locator('[data-test="source-url"]')->fill(self::SYMFONY_FEED);
        $this->page->locator('[data-test="source-categories"] > div > label')->first()->click();
        $this->page->locator('[data-test="source-submit"]')->click();

        // Turbo soumet sans navigation : attendre la nouvelle ligne ou l'erreur du champ avant de décider.
        $error = $this->page->locator('[data-test="source-form"] [id^="source_feedUrl_error"]');
        $this->expect($this->symfonyRow()->or($error))->toBeVisible();
        if (0 < $error->count() && str_contains($error->innerText(), 'Flux injoignable')) {
            self::markTestSkipped('Flux Symfony injoignable depuis le serveur de dev.');
        }

        $row = $this->symfonyRow();
        $this->expect($row->locator('[data-test="source-name"]'))->toHaveText('Symfony Blog');
        $this->expect($row->locator('[data-test="source-fetched"]'))->toContainText('Pas encore relevée');

        $row->locator('[data-test="source-fetch"]')->click();
        $this->expect($this->page->locator('[role="alert"]', [
            'hasText' => 'Relève de « Symfony Blog »',
        ]))->toBeVisible();
        $this->expect($this->symfonyRow()->locator('[data-test="source-fetched"]'))->toContainText(
            'Dernière relève réussie',
        );

        $this->symfonyRow()->locator('[data-test="source-remove"]')->click();
        $this->expect($this->page->locator('[role="alert"]'))->toContainText('Source « Symfony Blog » retirée.');
        $this->expect($this->symfonyRow())->toHaveCount(0);
    }

    public function testMemberWhoIsNotAdminHasNoSourcesEntry(): void
    {
        $this->page->goto('/login');
        $this->page->locator('input[name="_username"]')->fill('marie@example.com');
        $this->page->locator('input[name="_password"]')->fill('password');
        $this->page->click('button[type="submit"]');
        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/');

        $this->expect($this->page->locator('[data-test="nav-sources"]'))->toHaveCount(0);
        $this->expect($this->page->locator('[data-test="nav-admins"]'))->toHaveCount(0);
        self::assertSame(403, $this->page->goto('/sources')?->status());
    }

    public function testCardOfASourceShowsItsBadge(): void
    {
        $this->logIn();

        $this->page->goto('/?q=' . rawurlencode('LLM Voice'));

        $card = $this->page->locator('[data-test="link-card"]')->first();
        $this->expect($card->locator('[data-test="link-auto"]'))->toContainText('auto · Martin Fowler');
        $this->expect($card->locator('[data-test="link-poster"]'))->toHaveText('radar');
    }

    /** Un passage interrompu a pu laisser le flux suivi. */
    private function removeSymfonyFeed(): void
    {
        if (0 === $this->symfonyRow()->count()) {
            return;
        }

        $this->symfonyRow()->locator('[data-test="source-remove"]')->click();
        $this->expect($this->symfonyRow())->toHaveCount(0);
    }

    private function symfonyRow(): LocatorInterface
    {
        return $this->page->locator('[data-test="source-row"]', ['hasText' => self::SYMFONY_FEED]);
    }
}
