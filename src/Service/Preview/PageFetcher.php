<?php

declare(strict_types=1);

namespace App\Service\Preview;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Téléchargement borné de la page d'un lien. Le JSON-LD (auteur, date) vit souvent en fin de `<body>` :
 * la lecture va donc jusqu'au plafond plutôt que de s'arrêter à `</head>`.
 */
final readonly class PageFetcher
{
    private const int MAX_BYTES = 512 * 1024;

    public function __construct(
        #[Autowire(service: 'app.preview_http_client')]
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {}

    /** Page HTML, aperçu réduit à l'image elle-même si l'adresse est une image, `null` sinon. */
    public function fetch(string $url): HtmlPage|LinkPreview|null
    {
        if (!in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], strict: true)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', $url, ['headers' => [
                'Accept' => 'text/html,application/xhtml+xml,image/*;q=0.8',
                'Accept-Language' => 'fr,en;q=0.8',
            ]]);

            $contentType = strtolower($response->getHeaders()['content-type'][0] ?? '');
            $finalUrl = (string) $response->getInfo('url');

            if (str_starts_with($contentType, 'image/')) {
                $response->cancel();

                return new LinkPreview(imageUrl: $finalUrl);
            }

            // Hors connexion, YouTube redirige un serveur européen vers sa page de consentement, sans intérêt.
            if (
                !str_starts_with($contentType, 'text/html')
                || str_starts_with((string) parse_url($finalUrl, PHP_URL_HOST), 'consent.')
            ) {
                $response->cancel();

                return null;
            }

            return HtmlPage::fromHtml($this->readPage($response), $finalUrl, $this->charset($contentType));
        } catch (ExceptionInterface $exception) {
            $this->logger->info('Aperçu indisponible pour {url} : {message}', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function readPage(ResponseInterface $response): string
    {
        $html = '';
        foreach ($this->httpClient->stream($response) as $chunk) {
            $html .= $chunk->getContent();
            if (strlen($html) >= self::MAX_BYTES) {
                $response->cancel();

                break;
            }
        }

        return substr($html, offset: 0, length: self::MAX_BYTES);
    }

    private function charset(string $contentType): ?string
    {
        $matches = [];

        return 1 === preg_match('/charset=["\']?([\w-]+)/', $contentType, $matches) ? $matches[1] ?? null : null;
    }
}
