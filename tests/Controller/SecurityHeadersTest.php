<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityHeadersTest extends WebTestCase
{
    /** @return iterable<string, array{string, int}> */
    public static function responses(): iterable
    {
        yield 'page HTML' => ['/login', 200];
        yield 'API sans clé' => ['/api/extension/categories', 401];
        yield 'média sans connexion' => ['/media/previews/' . str_repeat('a', times: 64) . '.webp', 302];
        yield 'bandeau absent' => ['/annonces/bandeaux/' . str_repeat('a', times: 64) . '.jpg', 404];
    }

    #[DataProvider('responses')]
    public function testEveryResponseForbidsFraming(string $path, int $status): void
    {
        // En debug, `ErrorListener::removeCspHeader()` retire la CSP des erreurs : la prod seule fait foi.
        $client = self::createClient(['debug' => false]);

        $client->request('GET', $path);

        self::assertResponseStatusCodeSame($status);
        self::assertResponseHeaderSame('X-Frame-Options', 'DENY');
        self::assertResponseHeaderSame('Content-Security-Policy', "frame-ancestors 'none'");
        self::assertResponseHeaderSame('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function testHstsIsSentOverHttps(): void
    {
        $client = self::createClient();

        $client->request('GET', '/login', server: ['HTTPS' => 'on']);

        self::assertResponseHeaderSame('Strict-Transport-Security', 'max-age=31536000');
    }

    public function testHstsIsNotSentOverHttp(): void
    {
        $client = self::createClient();

        $client->request('GET', '/login');

        self::assertResponseNotHasHeader('Strict-Transport-Security');
    }
}
