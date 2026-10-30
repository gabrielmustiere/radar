<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Exception\InvalidFeedException;
use App\Feed\Feed;
use App\Feed\FeedItem;
use App\Feed\FeedParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FeedParserTest extends TestCase
{
    public function testReadsTitleAndArticlesOfTheSymfonyBlogInDocumentOrder(): void
    {
        $feed = new FeedParser()->parse((string) file_get_contents(__DIR__ . '/../Fixtures/feed/symfony-blog.xml'));

        self::assertSame('Symfony Blog', $feed->title);
        self::assertCount(3, $feed->items);
        $first = $feed->items[0] ?? null;
        self::assertInstanceOf(FeedItem::class, $first);
        self::assertSame(
            'https://symfony.com/blog/new-in-symfony-8-2-recovering-from-controller-attribute-failures'
            . '?utm_source=Symfony%20Blog%20Feed&utm_medium=feed',
            $first->url,
        );
        self::assertSame($first->url, $first->key);
        self::assertStringContainsString('hackathon', self::urls($feed)[2] ?? '');
    }

    public function testReadsAtomEntriesByIdAndAlternateLinkAndSkipsRelativeLinks(): void
    {
        $feed = new FeedParser()->parse((string) file_get_contents(__DIR__ . '/../Fixtures/feed/atom.xml'));

        self::assertSame('Journal de test', $feed->title);
        self::assertSame(['tag:example.com,2026:2', 'tag:example.com,2026:1'], $feed->keys());
        self::assertSame(['https://example.com/billets/2', 'https://example.com/billets/1'], self::urls($feed));
    }

    public function testReadsPublicationDatesOfRssAndAtomArticles(): void
    {
        $parser = new FeedParser();
        $rss = $parser->parse((string) file_get_contents(__DIR__ . '/../Fixtures/feed/symfony-blog.xml'));
        $atom = $parser->parse(<<<'XML'
            <feed xmlns="http://www.w3.org/2005/Atom"><title>A</title>
            <entry><id>1</id><link href="https://example.com/1"/><published>2026-10-29T08:00:00Z</published><updated>2026-10-30T08:00:00Z</updated></entry>
            <entry><id>2</id><link href="https://example.com/2"/><updated>2026-10-30T08:00:00Z</updated></entry>
            <entry><id>3</id><link href="https://example.com/3"/><updated>pas une date</updated></entry>
            </feed>
            XML);

        self::assertSame(
            ['2026-10-30T09:26:00+01:00', '2026-10-29T14:30:00+01:00', '2026-10-29T11:30:00+01:00'],
            array_map(static fn(FeedItem $item): ?string => $item->publishedAt?->format('c'), $rss->items),
        );
        self::assertSame(
            ['2026-10-29T08:00:00+00:00', '2026-10-30T08:00:00+00:00', null],
            array_map(static fn(FeedItem $item): ?string => $item->publishedAt?->format('c'), $atom->items),
        );
        $recent = $rss->items[0] ?? null;
        $undated = $atom->items[2] ?? null;
        self::assertInstanceOf(FeedItem::class, $recent);
        self::assertInstanceOf(FeedItem::class, $undated);
        self::assertTrue($recent->isPublishedSince(new \DateTimeImmutable('2026-10-30 08:00:00+01:00')));
        self::assertFalse($undated->isPublishedSince(new \DateTimeImmutable('2000-01-01')));
    }

    public function testRssItemWithoutGuidIsKeyedByItsLinkAndRepeatedKeysCountOnce(): void
    {
        $feed = new FeedParser()->parse(<<<'XML'
            <rss version="2.0"><channel><title>T</title>
            <item><link>https://example.com/a</link></item>
            <item><link>https://example.com/a</link></item>
            <item><link>ftp://example.com/b</link></item>
            <item><title>Sans lien</title></item>
            </channel></rss>
            XML);

        self::assertSame(['https://example.com/a'], $feed->keys());
    }

    public function testAtomLinkInsideRssItemIsNotTakenForTheArticleLink(): void
    {
        $feed = new FeedParser()->parse(<<<'XML'
            <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel><title>T</title>
            <item><atom:link href="https://example.com/self"/><link>https://example.com/article</link></item>
            </channel></rss>
            XML);

        self::assertSame(['https://example.com/article'], self::urls($feed));
    }

    public function testExternalEntityIsNeverResolved(): void
    {
        $feed = new FeedParser()->parse(<<<'XML'
            <?xml version="1.0"?>
            <!DOCTYPE rss [<!ENTITY secret SYSTEM "file:///etc/hosts">]>
            <rss version="2.0"><channel><title>&secret;</title></channel></rss>
            XML);

        self::assertSame('', $feed->title);
    }

    #[DataProvider('unreadableDocuments')]
    public function testRefusesWhatIsNotARssOrAtomFeed(string $document, string $message): void
    {
        try {
            new FeedParser()->parse($document);
            self::fail('Le document aurait dû être refusé.');
        } catch (InvalidFeedException $exception) {
            self::assertStringContainsString($message, $exception->getMessage());
        }
    }

    /** @return \Generator<string, array{string, string}> */
    public static function unreadableDocuments(): \Generator
    {
        yield 'vide' => ['  ', 'Le flux est vide.'];
        yield 'XML invalide' => ['<rss><channel>', 'ce n\'est pas du XML valide'];
        yield 'page HTML' => ['<html><body>Bonjour</body></html>', 'ne mène pas à un flux RSS ou Atom'];
        yield 'RSS sans canal' => ['<rss version="2.0"/>', 'n\'a pas de canal'];
        yield 'feed hors espace Atom' => ['<feed><title>T</title></feed>', 'ne mène pas à un flux RSS ou Atom'];
    }

    /** @return list<string> */
    private static function urls(Feed $feed): array
    {
        return array_map(static fn(FeedItem $item): string => $item->url, $feed->items);
    }
}
