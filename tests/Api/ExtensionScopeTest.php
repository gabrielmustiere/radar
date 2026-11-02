<?php

declare(strict_types=1);

namespace App\Tests\Api;

/**
 * Ce que la clé n'ouvre pas, ce qui n'ouvre pas l'API, et le frein aux clés refusées.
 */
final class ExtensionScopeTest extends ExtensionApiTestCase
{
    public function testKeyDoesNotAuthenticateOutsideTheApi(): void
    {
        $key = $this->keyFor($this->member());

        $this->api('GET', '/', $key);

        self::assertResponseRedirects('http://localhost/login');
    }

    public function testSessionDoesNotAuthenticateTheApi(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler
            ->selectButton('Se connecter')
            ->form([
                '_username' => 'admin@example.com',
                '_password' => 'password',
            ]));
        self::assertResponseRedirects('http://localhost/');

        $this->api('GET', '/api/extension/categories', key: null);

        self::assertResponseStatusCodeSame(401);
    }

    public function testRepeatedFailuresThrottleTheAddress(): void
    {
        $key = $this->keyFor($this->member());
        $ip = self::randomIp('172');
        for ($attempt = 0; $attempt < 10; ++$attempt) {
            $this->api('GET', '/api/extension/categories', 'rdr_wrong', ip: $ip);
            self::assertResponseStatusCodeSame(401);
        }

        $this->api('GET', '/api/extension/categories', $key, ip: $ip);
        self::assertResponseStatusCodeSame(429);

        $this->api('GET', '/api/extension/categories', $key);
        self::assertResponseIsSuccessful();
    }

    public function testApiDocumentationIsNotExposed(): void
    {
        $key = $this->keyFor($this->member());

        foreach (['/api', '/api/docs', '/api/docs.jsonopenapi', '/api/docs.jsonld'] as $uri) {
            $this->api('GET', $uri, $key);
            self::assertResponseStatusCodeSame(404, $uri);
        }
    }
}
