<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Service\Preview\HtmlPage;
use App\Service\Preview\LinkPreview;
use App\Service\Preview\PageFetcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PageFetcherTest extends TestCase
{
    public function testReadsHtmlPageAtItsFinalAddress(): void
    {
        $client = new MockHttpClient(new MockResponse('<title>Une page</title>', [
            'response_headers' => ['content-type' => 'text/html; charset=utf-8'],
        ]));

        $page = new PageFetcher($client, new NullLogger())->fetch('https://example.com/page');

        self::assertInstanceOf(HtmlPage::class, $page);
        self::assertSame('Une page', $page->title());
        self::assertSame('https://example.com/page', $page->url);
    }

    public function testReadsJsonLdPlacedAtTheEndOfBody(): void
    {
        $response = new MockResponse([
            '<html><head><title>Titre</title></head><body><p>Article</p>',
            '<script type="application/ld+json">{"@type": "Article", "author": "Camille Martin"}</script></body></html>',
        ], [
            'response_headers' => ['content-type' => 'text/html'],
        ]);

        $page = new PageFetcher(new MockHttpClient($response), new NullLogger())->fetch('https://example.com/');

        self::assertInstanceOf(HtmlPage::class, $page);
        self::assertCount(1, $page->jsonLdBlocks());
    }

    public function testCapsReadingAt512Kilobytes(): void
    {
        $chunks = array_fill(0, count: 40, value: str_repeat('x', times: 100_000));
        $response = new MockResponse(['<title>Titre</title>', ...$chunks], ['response_headers' => [
            'content-type' => 'text/html',
        ]]);

        new PageFetcher(new MockHttpClient($response), new NullLogger())->fetch('https://example.com/');

        self::assertLessThan(1_000_000, $response->getInfo('size_download'));
    }

    public function testImageAddressIsReportedAsImage(): void
    {
        $client = new MockHttpClient(new MockResponse('PNG', ['response_headers' => ['content-type' => 'image/png']]));

        $result = new PageFetcher($client, new NullLogger())->fetch('https://example.com/a.png');

        self::assertEquals(new LinkPreview(imageUrl: 'https://example.com/a.png'), $result);
    }

    #[DataProvider('unusableResponses')]
    public function testReturnsNullForUnusableResponse(MockResponse $response): void
    {
        self::assertNull(new PageFetcher(new MockHttpClient($response), new NullLogger())->fetch(
            'https://example.com/',
        ));
    }

    /** @return \Generator<string, array{MockResponse}> */
    public static function unusableResponses(): \Generator
    {
        yield 'erreur HTTP' => [new MockResponse('', ['http_code' => 404])];
        yield 'erreur réseau' => [new MockResponse('', ['error' => 'Timeout'])];
        yield 'document non HTML' => [new MockResponse('%PDF', ['response_headers' => [
            'content-type' => 'application/pdf',
        ]])];
        yield 'XHTML servi en XML' => [new MockResponse('<x/>', ['response_headers' => [
            'content-type' => 'application/xml',
        ]])];
    }

    public function testIgnoresYouTubeConsentPage(): void
    {
        $client = new MockHttpClient(new MockResponse('<title>Avant d\'accéder à YouTube</title>', [
            'response_headers' => ['content-type' => 'text/html'],
            'url' => 'https://consent.youtube.com/m?continue=x',
        ]));

        self::assertNull(new PageFetcher($client, new NullLogger())->fetch(
            'https://www.youtube.com/watch?v=qjSAMOkrdIQ',
        ));
    }

    public function testRefusesNonHttpScheme(): void
    {
        $client = new MockHttpClient();

        self::assertNull(new PageFetcher($client, new NullLogger())->fetch('ftp://example.com/file'));
        self::assertSame(0, $client->getRequestsCount());
    }
}
