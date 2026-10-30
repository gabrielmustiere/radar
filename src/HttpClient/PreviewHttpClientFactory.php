<?php

declare(strict_types=1);

namespace App\HttpClient;

use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client des aperçus : les adresses viennent des membres, il ne doit jamais atteindre le réseau interne.
 */
final class PreviewHttpClientFactory
{
    /**
     * `NoPrivateNetworkHttpClient` suit lui-même les redirections, en contrôlant l'IP de chaque saut avec ses propres
     * options : elles sont donc posées sur lui. Le limiteur, placé dessous, voit chaque saut et son hôte.
     */
    public static function create(
        HttpClientInterface $client,
        RateLimiterFactoryInterface $limiterFactory,
        string $userAgent,
    ): HttpClientInterface {
        return new NoPrivateNetworkHttpClient(new PerHostThrottlingHttpClient($client, $limiterFactory))->withOptions([
            'headers' => ['User-Agent' => $userAgent],
            'timeout' => 5,
            'max_duration' => 10,
            'max_redirects' => 3,
        ]);
    }
}
