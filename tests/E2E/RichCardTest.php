<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use Playwright\Locator\LocatorInterface;

/**
 * Cartes enrichies sur le serveur de dev : vraies sources, worker Messenger, visuels conservés par Radar.
 */
final class RichCardTest extends E2ETestCase
{
    /** Enrichissement réel par le worker (rafraîchissement de la carte toutes les 3 s). */
    private const int ENRICHMENT_TIMEOUT_MS = 45_000;

    public function testVideoCardLoadsPlayerOnlyAfterClick(): void
    {
        $this->logIn();
        $url = $this->freeRealLink('https://www.youtube.com/');

        $card = $this->addEnrichedLink($url);

        $this->expect($card)->toHaveAttribute('data-preview-status', 'ready');
        $this->expect($card->locator('[data-test="link-video-play"]'))->toBeVisible();
        $this->expect($card->locator('iframe'))->toHaveCount(0);
        $this->assertServedByRadar($card->locator('[data-test="link-card-image"]'));

        $card->locator('[data-test="link-video-play"]')->click();

        $player = $card->locator('[data-test="link-video-player"]');
        $this->expect($player)->toBeVisible();
        $videoId = (string) preg_replace('/^.*[?&]v=([\w-]{11}).*$/', replacement: '$1', subject: $url);
        $this->expect($player)->toHaveAttribute(
            'src',
            'https://www.youtube-nocookie.com/embed/' . $videoId . '?autoplay=1',
        );
    }

    public function testArticleCardShowsImageKeptByRadar(): void
    {
        $this->logIn();

        $card = $this->addEnrichedLink($this->freeRealLink('https://simonwillison.net/'));

        $this->expect($card)->toHaveAttribute('data-preview-status', 'ready');
        $this->expect($card->locator('[data-test="link-title"]'))->not()->toContainText('https://');
        $this->assertServedByRadar($card->locator('[data-test="link-card-image"]'));
    }

    public function testXPostShowsAuthorOrFallsBack(): void
    {
        $this->logIn();

        $card = $this->addEnrichedLink($this->freeRealLink('https://x.com/'));

        // L'oEmbed de X est un service tiers : sans réponse, le post n'affiche que son compte, tiré de l'adresse.
        if ('ready' !== $card->getAttribute('data-preview-status')) {
            $this->expect($card->locator('[data-test="link-handle"]'))->toBeVisible();

            return;
        }

        $this->expect($card->locator('[data-test="link-author"]'))->toBeVisible();
        $this->expect($card->locator('[data-test="link-description"]'))->toBeVisible();
    }

    public function testCardsFitAPhoneScreen(): void
    {
        $this->logIn();
        $this->page->setViewportSize(390, 844);

        $this->page->goto('/');
        $this->expect($this->page->locator('[data-test="link-card"]')->first())->toBeVisible();

        self::assertTrue($this->page->evaluate('document.documentElement.scrollWidth <= window.innerWidth'));
    }

    /** Ajoute le lien dans la première catégorie, puis attend que sa carte quitte l'état « en cours ». */
    private function addEnrichedLink(string $url): LocatorInterface
    {
        $this->page->click('[data-test="add-link"]');
        $modal = $this->page->locator('[data-test="link-modal"]');
        $modal->locator('[data-test="link-url"]')->fill($url);
        $modal->locator('[data-test="link-categories"] > div > label')->first()->click();
        $modal->locator('[data-test="link-submit"]')->click();
        $this->expect($modal)->toBeHidden();

        $card = $this->page->locator('[data-test="link-card"]')->first();
        $this->expect($card->locator('[data-test="link-open"]'))->toHaveAttribute('title', 'Ouvrir ' . $url);
        $this
            ->expect($card)
            ->withTimeout(self::ENRICHMENT_TIMEOUT_MS)
            ->not()
            ->toHaveAttribute('data-preview-status', 'pending');

        return $card;
    }

    /** L'image vient de Radar (`/media/`), jamais de la source, et elle s'affiche réellement. */
    private function assertServedByRadar(LocatorInterface $image): void
    {
        $image->scrollIntoViewIfNeeded();
        self::assertStringStartsWith('/media/previews/', (string) $image->getAttribute('src'));
        self::assertSame('lazy', $image->getAttribute('loading'));
        self::assertNotSame('', (string) $image->getAttribute('alt'));
        self::assertGreaterThan(0, $image->evaluate('img => img.decode().then(() => img.naturalWidth)'));
    }
}
