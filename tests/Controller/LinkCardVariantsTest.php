<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Service\Preview\LinkPreview;
use App\Service\Preview\PreviewImageStore;

final class LinkCardVariantsTest extends EnrichedLinkWebTestCase
{
    public function testVideoShowsFacadeWithoutLoadingPlayer(): void
    {
        $client = $this->loggedClient();
        $image = PreviewImageStore::previewPath('https://i.ytimg.com/vi/qjSAMOkrdIQ/maxresdefault.jpg');
        $link = $this->enrichedLink(
            'https://www.youtube.com/watch?v=qjSAMOkrdIQ&u=' . uniqid(),
            new LinkPreview(title: '20 Years of Symfony', siteName: 'YouTube', author: 'Symfony'),
            $image,
        );

        $card = $this->showCard($client, $link);

        self::assertSelectorExists($card . ' [data-test="link-video"][data-video-facade-video-id-value="qjSAMOkrdIQ"]');
        self::assertSelectorExists(sprintf('%s [data-test="link-video"] img[src="/media/%s"]', $card, $image));
        self::assertSelectorExists($card . ' button[data-test="link-video-play"][aria-label*="20 Years of Symfony"]');
        self::assertSelectorNotExists($card . ' iframe');
        self::assertSelectorTextContains($card . ' [data-test="link-author"]', 'Symfony');
    }

    public function testRepositoryShowsStarsAndLanguage(): void
    {
        $client = $this->loggedClient();
        $link = $this->enrichedLink(
            'https://github.com/symfony/' . uniqid('depot-'),
            new LinkPreview(title: 'symfony/symfony', githubStars: 30_812, githubLanguage: 'PHP'),
        );

        $card = $this->showCard($client, $link);

        self::assertSelectorTextContains($card . ' [data-test="link-stars"]', '30 812');
        self::assertSelectorTextContains($card . ' [data-test="link-language"]', 'PHP');
    }

    public function testArticleShowsAuthorAndPublicationDateApartFromAddedDate(): void
    {
        $client = $this->loggedClient();
        $link = $this->enrichedLink(
            'https://blog.example.com/' . uniqid('article-'),
            new LinkPreview(
                title: 'Les tests de contrat',
                author: 'Camille Martin',
                publishedAt: new \DateTimeImmutable('2025-03-12T08:30:00+01:00'),
            ),
        );

        $card = $this->showCard($client, $link);

        self::assertSelectorTextSame($card . ' [data-test="link-byline"]', 'Par Camille Martin · publié le 12/03/2025');
        self::assertSelectorTextSame($card . ' [data-test="link-date"]', new \DateTimeImmutable()->format('d/m/Y'));
    }

    public function testXPostShowsAuthorAndPlainText(): void
    {
        $client = $this->loggedClient();
        $link = $this->enrichedLink(
            'https://x.com/symfony/status/' . random_int(1, PHP_INT_MAX),
            new LinkPreview(
                title: 'Symfony sur X',
                description: '<b>Pas</b> de HTML interprété',
                siteName: 'X',
                author: 'Symfony',
            ),
        );

        $card = $this->showCard($client, $link);

        self::assertSelectorTextContains($card . ' [data-test="link-author"]', 'Symfony');
        self::assertSelectorTextSame($card . ' [data-test="link-description"]', '<b>Pas</b> de HTML interprété');
        self::assertSelectorNotExists($card . ' [data-test="link-description"] b');
    }

    public function testFallbackShowsStoredFaviconInsteadOfSourceIcon(): void
    {
        $client = $this->loggedClient();
        $favicon = PreviewImageStore::faviconPath('https://blog.example.com/icon.png');
        $link = $this->enrichedLink(
            'https://blog.example.com/' . uniqid('sans-image-'),
            new LinkPreview(title: 'Sans image'),
            faviconPath: $favicon,
        );

        $card = $this->showCard($client, $link);

        self::assertSelectorExists(sprintf(
            '%s [data-test="link-card-fallback"] img[src="/media/%s"]',
            $card,
            $favicon,
        ));
        self::assertSelectorNotExists($card . ' [data-test="link-card-image"]');
    }
}
