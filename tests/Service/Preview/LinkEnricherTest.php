<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Enum\Type\PreviewStatus;
use App\Service\Preview\PreviewImageStore;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class LinkEnricherTest extends TestCase
{
    public function testAppliesTimestampedSnapshot(): void
    {
        $link = PreviewTestKit::link('https://example.com/post');
        $html =
            new MockResponse('<meta property="og:title" content="Titre"><meta property="article:published_time" content="2026-10-01">', ['response_headers' => [
                'content-type' => 'text/html',
            ]]);

        PreviewTestKit::enricher(new MockHttpClient($html))->enrich($link);

        self::assertSame(PreviewStatus::Ready, $link->getPreviewStatus());
        self::assertSame('Titre', $link->getTitle());
        self::assertSame('2026-10-01', $link->getPublishedAt()?->format('Y-m-d'));
        self::assertEquals(new MockClock(PreviewTestKit::NOW)->now(), $link->getPreviewFetchedAt());
    }

    public function testStoresPreviewImageAndKeepsSourceAddress(): void
    {
        $link = PreviewTestKit::link('https://example.com/post');
        $storage = new Filesystem(new InMemoryFilesystemAdapter());
        $client = new MockHttpClient([
            new MockResponse('<meta property="og:image" content="/cover.png">', [
                'response_headers' => ['content-type' => 'text/html'],
            ]),
            new MockResponse(TestImage::png()),
        ]);

        PreviewTestKit::enricher($client, $storage)->enrich($link);

        self::assertSame('https://example.com/cover.png', $link->getImageUrl());
        self::assertSame(PreviewImageStore::previewPath('https://example.com/cover.png'), $link->getImagePath());
        self::assertTrue($storage->fileExists((string) $link->getImagePath()));
    }

    public function testMarksPreviewUnavailableWhenSourceFails(): void
    {
        $link = PreviewTestKit::link('https://example.com/post');

        PreviewTestKit::enricher(new MockHttpClient(new MockResponse('', ['http_code' => 500])))->enrich($link);

        self::assertSame(PreviewStatus::Unavailable, $link->getPreviewStatus());
        self::assertEquals(new MockClock(PreviewTestKit::NOW)->now(), $link->getPreviewFetchedAt());
    }

    public function testPartialRefreshKeepsWhatWasAlreadyKnown(): void
    {
        $link = PreviewTestKit::link('https://example.com/post');
        $storage = new Filesystem(new InMemoryFilesystemAdapter());
        $page = static fn(string $head): MockResponse => new MockResponse($head, [
            'response_headers' => ['content-type' => 'text/html'],
        ]);
        PreviewTestKit::enricher(new MockHttpClient([
            $page(
                '<meta property="og:title" content="Titre"><meta property="og:description" content="Résumé">'
                . '<meta property="og:image" content="/cover.png">',
            ),
            new MockResponse(TestImage::png()),
        ]), $storage)->enrich($link);
        $imagePath = $link->getImagePath();

        PreviewTestKit::enricher(new MockHttpClient([
            $page('<meta property="og:title" content="Nouveau titre"><meta property="og:image" content="/cover.png">'),
            new MockResponse('', ['http_code' => 429]),
        ]), $storage)->enrich($link);

        self::assertSame('Nouveau titre', $link->getTitle());
        self::assertSame('Résumé', $link->getDescription());
        self::assertNotNull($imagePath);
        self::assertSame($imagePath, $link->getImagePath());
    }

    public function testFailedRefreshKeepsPreviousSnapshot(): void
    {
        $link = PreviewTestKit::link('https://example.com/post');
        $html = new MockResponse('<title>Titre</title>', ['response_headers' => ['content-type' => 'text/html']]);
        PreviewTestKit::enricher(new MockHttpClient($html))->enrich($link);

        PreviewTestKit::enricher(new MockHttpClient(new MockResponse('', ['http_code' => 503])))->enrich($link);

        self::assertSame(PreviewStatus::Ready, $link->getPreviewStatus());
        self::assertSame('Titre', $link->getTitle());
    }
}
