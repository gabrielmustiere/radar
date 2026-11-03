<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\LinkRepository;
use App\Service\Preview\LinkPreview;
use PHPUnit\Framework\Attributes\DataProvider;

final class LinkCardBadgeTest extends EnrichedLinkWebTestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function types(): iterable
    {
        yield 'vidéo' => ['https://www.youtube.com/watch?v=qjSAMOkrdIQ&u=', 'Vidéo YouTube'];
        yield 'dépôt' => ['https://github.com/symfony/', 'Dépôt GitHub'];
        yield 'post X' => ['https://x.com/symfony/status/', 'Post X'];
        yield 'article' => ['https://blog.example.com/', 'Article'];
        yield 'image' => ['https://images.example.com/photo.png?u=', 'Image'];
    }

    #[DataProvider('types')]
    public function testCardShowsItsTypeBadge(string $url, string $label): void
    {
        $client = $this->loggedClient();
        $link = $this->enrichedLink($url . random_int(1, PHP_INT_MAX), new LinkPreview(title: 'Badge'));

        $card = $this->showCard($client, $link);

        self::assertSelectorTextSame($card . ' [data-test="link-type"]', $label);
    }

    public function testLinkPublishedByAFollowedSourceShowsSourceBadge(): void
    {
        $client = $this->loggedClient();
        $links = self::getContainer()->get(LinkRepository::class);
        self::assertInstanceOf(LinkRepository::class, $links);
        $link = $links->findOneByNormalizedUrl('https://martinfowler.com/bliki/LLM-Voice.html');
        self::assertNotNull($link);
        self::assertNotNull($link->getSource());

        $client->request('GET', sprintf('/liens/%d', $link->getId()));

        self::assertSelectorTextSame('[data-test="link-card"] [data-test="link-type"]', 'Source suivie');
    }

    public function testLinkPostedLessThan72HoursAgoIsNew(): void
    {
        $client = $this->loggedClient();
        $recent = $this->enrichedLink(
            'https://blog.example.com/' . uniqid('recent-'),
            new LinkPreview(title: 'Récent'),
            postedAt: new \DateTimeImmutable('-71 hours'),
        );
        $old = $this->enrichedLink(
            'https://blog.example.com/' . uniqid('ancien-'),
            new LinkPreview(title: 'Ancien'),
            postedAt: new \DateTimeImmutable('-73 hours'),
        );

        self::assertSelectorTextSame($this->showCard($client, $recent) . ' [data-test="link-new"]', 'Nouveau');
        self::assertSelectorNotExists($this->showCard($client, $old) . ' [data-test="link-new"]');
    }
}
