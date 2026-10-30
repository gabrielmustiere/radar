<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview\Extractor;

use App\Service\Preview\Extractor\OEmbedExtractor;
use App\Service\Preview\HtmlPage;
use App\Service\Preview\JsonFetcher;
use App\Tests\Service\Preview\PreviewFixture;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

final class OEmbedExtractorTest extends TestCase
{
    public function testReadsDiscoveredEndpointOfKnownProvider(): void
    {
        $client = new MockHttpClient(new JsonMockResponse([
            'title' => 'Une conférence',
            'author_name' => 'Camille Martin',
            'provider_name' => 'Vimeo',
            'thumbnail_url' => 'https://i.vimeocdn.com/video/1.jpg',
            'html' => '<iframe src="https://player.vimeo.com/video/1"></iframe>',
        ]));
        $page = PreviewFixture::page('vimeo.html', 'https://vimeo.com/76979871');

        $preview = $this->extractor($client)->extract('https://vimeo.com/76979871', $page);

        self::assertSame('Une conférence', $preview->title);
        self::assertSame('Camille Martin', $preview->author);
        self::assertSame('Vimeo', $preview->siteName);
        self::assertSame('https://i.vimeocdn.com/video/1.jpg', $preview->imageUrl);
        self::assertSame(1, $client->getRequestsCount());
    }

    public function testIgnoresEndpointOfUnknownProvider(): void
    {
        $client = new MockHttpClient();
        $page = HtmlPage::fromHtml(
            '<link rel="alternate" type="application/json+oembed" href="https://evil.example.com/oembed">',
            'https://example.com/',
        );

        self::assertTrue($this->extractor($client)->extract('https://example.com/', $page)->isEmpty());
        self::assertSame(0, $client->getRequestsCount());
    }

    private function extractor(MockHttpClient $client): OEmbedExtractor
    {
        return new OEmbedExtractor(new JsonFetcher($client, new NullLogger()));
    }
}
