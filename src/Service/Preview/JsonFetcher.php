<?php

declare(strict_types=1);

namespace App\Service\Preview;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Lecture des services d'aperçu (oEmbed, API GitHub) par le client des aperçus : `null` au moindre échec.
 */
final readonly class JsonFetcher
{
    public function __construct(
        #[Autowire(service: 'app.preview_http_client')]
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, string> $headers
     *
     * @return array<array-key, mixed>|null
     */
    public function fetch(string $url, array $headers = []): ?array
    {
        try {
            return $this->httpClient->request('GET', $url, [
                'headers' => $headers + ['Accept' => 'application/json'],
            ])->toArray();
        } catch (ExceptionInterface $exception) {
            $this->logger->info('Service d\'aperçu indisponible pour {url} : {message}', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
