<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Service\Preview\HtmlPage;
use App\Service\Preview\PageFetcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Les adresses viennent des membres : jamais le réseau interne, même au détour d'une redirection.
 */
final class PageFetcherSsrfTest extends TestCase
{
    /** Adresse publique littérale : le contrôle d'IP n'a aucune résolution DNS à faire. */
    private const string PUBLIC_URL = 'https://93.184.215.14/post';

    #[DataProvider('privateAddresses')]
    public function testNeverQueriesPrivateNetwork(string $url): void
    {
        $requested = [];
        $client = PreviewTestKit::client($requested, [new MockResponse('<title>Interne</title>', [
            'response_headers' => ['content-type' => 'text/html'],
        ])]);

        self::assertNull(new PageFetcher($client, new NullLogger())->fetch($url));
        self::assertSame([], $requested);
    }

    /** @return \Generator<string, array{string}> */
    public static function privateAddresses(): \Generator
    {
        yield 'loopback IPv4' => ['http://127.0.0.1/admin'];
        yield 'réseau privé 10.x' => ['http://10.0.0.12/'];
        yield 'métadonnées cloud (link-local)' => ['http://169.254.169.254/latest/meta-data/'];
        yield 'loopback IPv6' => ['http://[::1]/'];
    }

    public function testFollowsRedirectBetweenPublicAddresses(): void
    {
        $requested = [];
        $guarded = PreviewTestKit::client($requested, [
            self::redirect('https://93.184.215.15/post'),
            new MockResponse('<title>Publique</title>', ['response_headers' => ['content-type' => 'text/html']]),
        ]);

        $page = new PageFetcher($guarded, new NullLogger())->fetch(self::PUBLIC_URL);

        self::assertSame('Publique', $page instanceof HtmlPage ? $page->title() : null);
        self::assertSame([self::PUBLIC_URL, 'https://93.184.215.15/post'], $requested);
    }

    public function testNeverFollowsRedirectFromPublicToPrivateNetwork(): void
    {
        $requested = [];
        $guarded = PreviewTestKit::client($requested, [
            self::redirect('http://127.0.0.1/admin'),
            new MockResponse('<title>Interne</title>', ['response_headers' => ['content-type' => 'text/html']]),
        ]);

        self::assertNull(new PageFetcher($guarded, new NullLogger())->fetch(self::PUBLIC_URL));
        self::assertSame([self::PUBLIC_URL], $requested);
    }

    public function testFollowsAtMostThreeRedirects(): void
    {
        $requested = [];
        $guarded = PreviewTestKit::client($requested, array_map(
            static fn(int $_): MockResponse => self::redirect(self::PUBLIC_URL),
            range(start: 1, end: 5),
        ));

        self::assertNull(new PageFetcher($guarded, new NullLogger())->fetch(self::PUBLIC_URL));
        self::assertCount(4, $requested);
    }

    /** `redirect_url` est ce qui fait suivre la redirection par le client, comme une vraie réponse 3xx. */
    private static function redirect(string $location): MockResponse
    {
        return new MockResponse('', [
            'http_code' => 302,
            'response_headers' => ['location' => $location],
            'redirect_url' => $location,
        ]);
    }
}
