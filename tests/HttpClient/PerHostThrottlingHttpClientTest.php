<?php

declare(strict_types=1);

namespace App\Tests\HttpClient;

use App\HttpClient\PerHostThrottlingHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class PerHostThrottlingHttpClientTest extends TestCase
{
    public function testThrottlesEachHostSeparately(): void
    {
        $storage = new InMemoryStorage();
        $factory = new RateLimiterFactory([
            'id' => 'preview_host',
            'policy' => 'token_bucket',
            'limit' => 1,
            'rate' => ['interval' => '1 hour'],
        ], $storage);
        $client = new PerHostThrottlingHttpClient(new MockHttpClient([
            new MockResponse(),
            new MockResponse(),
        ]), $factory);

        $client->request('GET', 'https://Example.com/a');
        $client->request('GET', 'https://other.example.com/b');

        self::assertFalse($factory->create('example.com')->consume()->isAccepted());
        self::assertFalse($factory->create('other.example.com')->consume()->isAccepted());
        self::assertTrue($factory->create('third.example.com')->consume()->isAccepted());
    }
}
