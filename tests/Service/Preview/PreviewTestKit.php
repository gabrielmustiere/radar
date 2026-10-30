<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Entity\Link;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\HttpClient\PreviewHttpClientFactory;
use App\Service\LinkUrlAnalyzer;
use App\Service\Preview\Extractor\HtmlExtractor;
use App\Service\Preview\Extractor\OpenGraphExtractor;
use App\Service\Preview\Extractor\XExtractor;
use App\Service\Preview\JsonFetcher;
use App\Service\Preview\LinkEnricher;
use App\Service\Preview\MetadataResolver;
use App\Service\Preview\PageFetcher;
use App\Service\Preview\PreviewImageStore;
use App\Service\Preview\TextTruncator;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Enrichissement et client des aperçus réels, sur des réponses HTTP simulées et un stockage en mémoire.
 */
final class PreviewTestKit
{
    public const string NOW = '2026-10-30 10:00:00';

    public static function enricher(MockHttpClient $client, ?FilesystemOperator $storage = null): LinkEnricher
    {
        $urlAnalyzer = new LinkUrlAnalyzer();
        $resolver = new MetadataResolver(
            new PageFetcher($client, new NullLogger()),
            $urlAnalyzer,
            new TextTruncator(),
            [
                new XExtractor($urlAnalyzer, new JsonFetcher($client, new NullLogger())),
                new OpenGraphExtractor(),
                new HtmlExtractor(),
            ],
        );

        return new LinkEnricher(
            $resolver,
            new PreviewImageStore(
                $client,
                $storage ?? new Filesystem(new InMemoryFilesystemAdapter()),
                new NullLogger(),
            ),
            new MockClock(self::NOW),
        );
    }

    public static function link(string $url, SourceType $sourceType = SourceType::Article): Link
    {
        return new Link(
            $url,
            $url,
            [],
            new User()->setEmail('marie@example.com'),
            new \DateTimeImmutable(),
            $sourceType,
        );
    }

    /**
     * Client des aperçus tel que la production le compose (`PreviewHttpClientFactory`), sur des réponses simulées.
     * Les URL sont relevées par la fabrique de réponses, partagée avec le clone créé par `withOptions()`.
     *
     * @param list<string> $requested
     * @param list<MockResponse> $responses
     */
    public static function client(
        array &$requested,
        array $responses,
        ?RateLimiterFactory $limiterFactory = null,
    ): HttpClientInterface {
        $factory = static function (string $_method, string $url) use (&$requested, &$responses): MockResponse {
            $requested[] = $url;

            return array_shift($responses) ?? new MockResponse('', ['http_code' => 500]);
        };
        $limiterFactory ??= new RateLimiterFactory([
            'id' => 'preview_host',
            'policy' => 'no_limit',
        ], new InMemoryStorage());

        return PreviewHttpClientFactory::create(new MockHttpClient($factory), $limiterFactory, 'RadarBot/1.0 (+test)');
    }
}
