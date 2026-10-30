<?php

declare(strict_types=1);

namespace App\Tests\HttpClient;

use App\Tests\Service\Preview\PreviewTestKit;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

/**
 * Composition du client des aperçus ; le refus du réseau interne est couvert par `PageFetcherSsrfTest`.
 */
final class PreviewHttpClientFactoryTest extends TestCase
{
    public function testIdentifiesItselfAndBoundsEachRequest(): void
    {
        $requested = [];
        $response = new MockResponse('ok');
        $client = PreviewTestKit::client($requested, [$response]);

        $client->request('GET', 'https://93.184.215.14/')->getContent();

        $options = (string) json_encode($response->getRequestOptions(), flags: JSON_UNESCAPED_SLASHES);
        self::assertStringContainsString('User-Agent: RadarBot/1.0 (+test)', $options);
        self::assertStringContainsString('"timeout":5,"max_duration":10', $options);
    }

    public function testThrottlesEveryRedirectHopOnItsOwnHost(): void
    {
        $requested = [];
        $limiterFactory = new RateLimiterFactory([
            'id' => 'preview_host',
            'policy' => 'token_bucket',
            'limit' => 1,
            'rate' => ['interval' => '1 hour'],
        ], new InMemoryStorage());
        $client = PreviewTestKit::client(
            $requested,
            [
                new MockResponse('', [
                    'http_code' => 302,
                    'response_headers' => ['location' => 'https://93.184.215.15/'],
                    'redirect_url' => 'https://93.184.215.15/',
                ]),
                new MockResponse('ok'),
            ],
            $limiterFactory,
        );

        $client->request('GET', 'https://93.184.215.14/')->getContent();

        self::assertSame(['https://93.184.215.14/', 'https://93.184.215.15/'], $requested);
        self::assertFalse($limiterFactory->create('93.184.215.14')->consume()->isAccepted());
        self::assertFalse($limiterFactory->create('93.184.215.15')->consume()->isAccepted());
    }
}
