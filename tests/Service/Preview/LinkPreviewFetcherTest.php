<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Service\Preview\LinkPreviewFetcher;
use App\Service\Preview\LinkPreviewParser;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class LinkPreviewFetcherTest extends TestCase
{
    public function testBuildsPreviewFromHtmlPage(): void
    {
        $response = new MockResponse('<title>Une page</title>', ['response_headers' => [
            'content-type' => 'text/html',
        ]]);

        $preview = $this->fetcher($response)->fetch('https://example.com/page');

        self::assertSame('Une page', $preview?->title);
    }

    public function testUsesImageItselfAsPreview(): void
    {
        $response = new MockResponse('PNG', ['response_headers' => ['content-type' => 'image/png']]);

        $preview = $this->fetcher($response)->fetch('https://example.com/a.png');

        self::assertSame('https://example.com/a.png', $preview?->imageUrl);
    }

    public function testReturnsNullOnHttpError(): void
    {
        self::assertNull($this->fetcher(new MockResponse('', [
            'http_code' => 404,
        ]))->fetch('https://example.com/missing'));
    }

    public function testReturnsNullOnNetworkError(): void
    {
        self::assertNull($this->fetcher(new MockResponse('', [
            'error' => 'Timeout',
        ]))->fetch('https://example.com/slow'));
    }

    public function testReturnsNullForNonHtmlDocument(): void
    {
        $response = new MockResponse('%PDF', ['response_headers' => ['content-type' => 'application/pdf']]);

        self::assertNull($this->fetcher($response)->fetch('https://example.com/doc.pdf'));
    }

    public function testReturnsNullForPageWithoutMetadata(): void
    {
        $response = new MockResponse('<p>Rien</p>', ['response_headers' => ['content-type' => 'text/html']]);

        self::assertNull($this->fetcher($response)->fetch('https://example.com/empty'));
    }

    private function fetcher(MockResponse $response): LinkPreviewFetcher
    {
        return new LinkPreviewFetcher(new MockHttpClient($response), new LinkPreviewParser(), new NullLogger());
    }
}
