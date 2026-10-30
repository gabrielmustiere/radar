<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Service\LinkUrlAnalyzer;
use App\Service\Preview\Extractor\MetadataExtractor;
use App\Service\Preview\HtmlPage;
use App\Service\Preview\LinkPreview;
use App\Service\Preview\MetadataResolver;
use App\Service\Preview\PageFetcher;
use App\Service\Preview\TextTruncator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class MetadataResolverTest extends TestCase
{
    public function testEachFieldComesFromTheMostPrioritySourceThatProvidesIt(): void
    {
        $resolver = $this->resolver(new MockHttpClient($this->html()), [
            $this->source(new LinkPreview(title: 'Adaptateur', githubStars: 12)),
            $this->source(new LinkPreview(
                title: 'OpenGraph',
                description: 'Description OG',
                imageUrl: 'https://example.com/og.png',
            )),
            $this->source(new LinkPreview(description: 'Description HTML', faviconUrl: 'https://example.com/icon.png')),
        ]);

        $preview = $resolver->resolve('https://example.com/post');

        self::assertSame('Adaptateur', $preview?->title);
        self::assertSame('Description OG', $preview->description);
        self::assertSame('https://example.com/og.png', $preview->imageUrl);
        self::assertSame('https://example.com/icon.png', $preview->faviconUrl);
        self::assertSame(12, $preview->githubStars);
    }

    public function testTruncatesTextsOnWordBoundary(): void
    {
        $resolver = $this->resolver(new MockHttpClient($this->html()), [
            $this->source(new LinkPreview(
                title: str_repeat('mot ', times: 40),
                description: str_repeat('phrase ', times: 60),
                author: str_repeat('Camille Martin, ', times: 30),
            )),
        ]);

        $preview = $resolver->resolve('https://example.com/post');

        self::assertLessThanOrEqual(100, mb_strlen((string) $preview?->title));
        self::assertStringEndsWith('mot…', (string) $preview?->title);
        self::assertLessThanOrEqual(250, mb_strlen((string) $preview?->description));
        self::assertStringEndsWith('phrase…', (string) $preview?->description);
        self::assertLessThanOrEqual(255, mb_strlen((string) $preview?->author));
    }

    public function testPassesDownloadedPageToSources(): void
    {
        $seen = [];
        $resolver = $this->resolver(new MockHttpClient($this->html()), [
            $this->source(new LinkPreview(title: 'Titre'), $seen),
        ]);

        $resolver->resolve('https://example.com/post');

        self::assertSame(['https://example.com/post'], $seen);
    }

    public function testDoesNotDownloadXPages(): void
    {
        $client = new MockHttpClient();
        $seen = [];
        $resolver = $this->resolver($client, [$this->source(new LinkPreview(title: 'Post'), $seen)]);

        self::assertSame('Post', $resolver->resolve('https://x.com/symfony/status/1')?->title);
        self::assertSame(0, $client->getRequestsCount());
        self::assertSame([null], $seen);
    }

    public function testImageAddressIsItsOwnPreview(): void
    {
        $client = new MockHttpClient(new MockResponse('PNG', ['response_headers' => ['content-type' => 'image/png']]));

        $preview = $this->resolver($client, [])->resolve('https://example.com/schema.png');

        self::assertSame('https://example.com/schema.png', $preview?->imageUrl);
    }

    public function testReturnsNullWhenNoSourceProvidesAnything(): void
    {
        $resolver = $this->resolver(new MockHttpClient($this->html()), [$this->source(new LinkPreview())]);

        self::assertNull($resolver->resolve('https://example.com/post'));
    }

    /** @param list<MetadataExtractor> $extractors */
    private function resolver(MockHttpClient $client, array $extractors): MetadataResolver
    {
        return new MetadataResolver(
            new PageFetcher($client, new NullLogger()),
            new LinkUrlAnalyzer(),
            new TextTruncator(),
            $extractors,
        );
    }

    /** @param list<?string> $seen URL de la page reçue à chaque appel, `null` sans page */
    private function source(LinkPreview $preview, array &$seen = []): MetadataExtractor
    {
        return new class($preview, $seen) implements MetadataExtractor {
            /** @param list<?string> $seen */
            public function __construct(
                private readonly LinkPreview $preview,
                private array &$seen,
            ) {}

            #[\Override]
            public function extract(string $url, ?HtmlPage $page): LinkPreview
            {
                $this->seen[] = $page?->url;

                return $this->preview;
            }
        };
    }

    private function html(): MockResponse
    {
        return new MockResponse('<html><head><title>Page</title></head></html>', [
            'response_headers' => ['content-type' => 'text/html; charset=utf-8'],
        ]);
    }
}
