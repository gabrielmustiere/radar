<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview\Extractor;

use App\Service\Preview\Extractor\YouTubeExtractor;
use App\Service\Preview\JsonFetcher;
use App\Tests\Service\Preview\PreviewFixture;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class YouTubeExtractorTest extends TestCase
{
    public function testReadsOEmbedAndKeepsHighResolutionThumbnail(): void
    {
        $client = new MockHttpClient([
            new MockResponse(PreviewFixture::content('youtube-oembed.json')),
            new MockResponse(''),
        ]);

        $preview = $this->extractor($client)->extract('https://youtu.be/qjSAMOkrdIQ', null);

        self::assertSame('Symfony 8 : les nouveautés', $preview->title);
        self::assertSame('SymfonyCasts', $preview->author);
        self::assertSame('YouTube', $preview->siteName);
        self::assertSame('https://i.ytimg.com/vi/qjSAMOkrdIQ/maxresdefault.jpg', $preview->imageUrl);
    }

    public function testFallsBackOnStandardThumbnailWhenHighResolutionIsMissing(): void
    {
        $client = new MockHttpClient([
            new MockResponse(PreviewFixture::content('youtube-oembed.json')),
            new MockResponse('', ['http_code' => 404]),
        ]);

        $preview = $this->extractor($client)->extract('https://www.youtube.com/watch?v=qjSAMOkrdIQ', null);

        self::assertSame('https://i.ytimg.com/vi/qjSAMOkrdIQ/hqdefault.jpg', $preview->imageUrl);
    }

    public function testGivesNoPreviewWhenVideoIsUnavailable(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 404]));

        self::assertTrue($this->extractor($client)->extract('https://youtu.be/qjSAMOkrdIQ', null)->isEmpty());
        self::assertSame(1, $client->getRequestsCount());
    }

    public function testIgnoresOtherAddresses(): void
    {
        $client = new MockHttpClient();

        self::assertTrue(
            $this->extractor($client)->extract('https://example.com/watch?v=qjSAMOkrdIQ', null)->isEmpty(),
        );
        self::assertSame(0, $client->getRequestsCount());
    }

    private function extractor(MockHttpClient $client): YouTubeExtractor
    {
        return new YouTubeExtractor(new JsonFetcher($client, new NullLogger()), $client);
    }
}
