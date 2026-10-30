<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Exception\InvalidFeedException;
use App\Feed\FeedParser;
use App\Feed\FeedReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FeedReaderTest extends TestCase
{
    public function testReadsAnXmlFeed(): void
    {
        $xml = (string) file_get_contents(__DIR__ . '/../Fixtures/feed/atom.xml');
        $reader = $this->reader(new MockResponse($xml, ['response_headers' => [
            'content-type' => 'application/atom+xml; charset=utf-8',
        ]]));

        self::assertSame('Journal de test', $reader->read('https://example.com/feed.atom')->title);
    }

    #[DataProvider('unreadableResponses')]
    public function testUnreadableResponseBecomesAReadableError(MockResponse $response, string $message): void
    {
        try {
            $this->reader($response)->read('https://example.com/feed');
            self::fail('La réponse aurait dû être refusée.');
        } catch (InvalidFeedException $exception) {
            self::assertStringContainsString($message, $exception->getMessage());
        }
    }

    /** @return \Generator<string, array{MockResponse, string}> */
    public static function unreadableResponses(): \Generator
    {
        yield 'page HTML' => [
            new MockResponse('<html></html>', ['response_headers' => ['content-type' => 'text/html']]),
            'ne mène pas à un flux RSS ou Atom',
        ];
        yield 'introuvable' => [new MockResponse('', ['http_code' => 404]), 'Flux injoignable'];
        yield 'au-delà de 2 Mo' => [
            new MockResponse(str_repeat('<a/>', times: 600_000), ['response_headers' => [
                'content-type' => 'text/xml',
            ]]),
            'Le flux dépasse 2 Mo.',
        ];
    }

    public function testRefusesNonHttpAddressWithoutRequest(): void
    {
        $this->expectException(InvalidFeedException::class);

        $this->reader(new MockResponse(''))->read('file:///etc/passwd');
    }

    private function reader(MockResponse $response): FeedReader
    {
        return new FeedReader(new MockHttpClient($response), new FeedParser());
    }
}
