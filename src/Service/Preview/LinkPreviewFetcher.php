<?php

declare(strict_types=1);

namespace App\Service\Preview;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class LinkPreviewFetcher
{
    private const int MAX_BYTES = 1_000_000;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LinkPreviewParser $parser,
        private LoggerInterface $logger,
    ) {}

    public function fetch(string $url): ?LinkPreview
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5,
                'max_duration' => 10,
                'max_redirects' => 5,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; RadarBot/1.0; veille interne)',
                    'Accept' => 'text/html,application/xhtml+xml,image/*;q=0.8',
                    'Accept-Language' => 'fr,en;q=0.8',
                ],
                'on_progress' => static function (int $downloaded): void {
                    if ($downloaded > self::MAX_BYTES) {
                        throw new \RuntimeException('Page trop volumineuse pour un aperçu.');
                    }
                },
            ]);

            $contentType = strtolower($response->getHeaders()['content-type'][0] ?? '');
            if (str_starts_with($contentType, 'image/')) {
                $response->cancel();

                return new LinkPreview(imageUrl: $url);
            }

            if (!str_contains($contentType, 'html')) {
                $response->cancel();

                return null;
            }

            $preview = $this->parser->parse($response->getContent(), (string) $response->getInfo('url'));

            return $preview->isEmpty() ? null : $preview;
        } catch (ExceptionInterface|\RuntimeException $exception) {
            $this->logger->info('Aperçu indisponible pour {url} : {message}', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
