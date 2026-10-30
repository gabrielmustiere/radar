<?php

declare(strict_types=1);

namespace App\HttpClient;

use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Espace les requêtes vers un même domaine cible (un limiteur par hôte), en attendant son tour plutôt qu'en échouant.
 */
final class PerHostThrottlingHttpClient implements HttpClientInterface
{
    use DecoratorTrait;

    public function __construct(
        HttpClientInterface $client,
        private readonly RateLimiterFactoryInterface $limiterFactory,
    ) {
        $this->client = $client;
    }

    /** @param array<array-key, mixed> $options */
    #[\Override]
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (is_string($host)) {
            $this->limiterFactory->create(strtolower($host))->reserve()->wait();
        }

        return $this->client->request($method, $url, $options);
    }
}
