<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview\Extractor;

use App\Service\LinkUrlAnalyzer;
use App\Service\Preview\Extractor\XExtractor;
use App\Service\Preview\JsonFetcher;
use App\Tests\Service\Preview\PreviewFixture;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class XExtractorTest extends TestCase
{
    public function testKeepsOnlyAuthorAndPlainTextOfPost(): void
    {
        $client = new MockHttpClient(new MockResponse(PreviewFixture::content('x-oembed.json')));

        $preview = $this->extractor($client)->extract('https://x.com/symfony/status/2115798561954648231', null);

        self::assertSame('Symfony sur X', $preview->title);
        self::assertSame('Symfony', $preview->author);
        self::assertSame('🚀 New in Symfony 8.2: Cross-Application Messaging https://t.co/abc', $preview->description);
        self::assertStringNotContainsString('<', $preview->description);
    }

    public function testReturnsEmptyPreviewWhenOEmbedFails(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 403]));

        self::assertTrue($this->extractor($client)->extract('https://x.com/symfony/status/1', null)->isEmpty());
    }

    public function testIgnoresProfiles(): void
    {
        $client = new MockHttpClient();

        self::assertTrue($this->extractor($client)->extract('https://x.com/symfony', null)->isEmpty());
        self::assertSame(0, $client->getRequestsCount());
    }

    private function extractor(MockHttpClient $client): XExtractor
    {
        return new XExtractor(new LinkUrlAnalyzer(), new JsonFetcher($client, new NullLogger()));
    }
}
