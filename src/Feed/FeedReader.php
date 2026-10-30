<?php

declare(strict_types=1);

namespace App\Feed;

use App\Exception\InvalidFeedException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Téléchargement d'un flux par le client des aperçus : l'adresse est saisie par un admin, elle ne doit jamais
 * atteindre le réseau interne. Un flux embarque souvent le contenu complet des articles, d'où un plafond large.
 */
final readonly class FeedReader
{
    private const int MAX_BYTES = 2 * 1024 * 1024;

    public function __construct(
        #[Autowire(service: 'app.preview_http_client')]
        private HttpClientInterface $httpClient,
        private FeedParser $parser,
    ) {}

    /** @throws InvalidFeedException */
    public function read(string $url): Feed
    {
        if (!in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], strict: true)) {
            throw new InvalidFeedException('Seules les adresses http(s) sont acceptées.');
        }

        try {
            $response = $this->httpClient->request('GET', $url, ['headers' => [
                'Accept' => 'application/rss+xml, application/atom+xml, application/xml;q=0.9, text/xml;q=0.9',
            ]]);

            $contentType = strtolower($response->getHeaders()['content-type'][0] ?? '');
            if (!str_contains($contentType, 'xml')) {
                $response->cancel();

                throw new InvalidFeedException('Cette adresse ne mène pas à un flux RSS ou Atom.');
            }

            $xml = '';
            foreach ($this->httpClient->stream($response) as $chunk) {
                $xml .= $chunk->getContent();
                if (strlen($xml) > self::MAX_BYTES) {
                    $response->cancel();

                    throw new InvalidFeedException('Le flux dépasse 2 Mo.');
                }
            }
        } catch (ExceptionInterface $exception) {
            throw new InvalidFeedException(
                sprintf('Flux injoignable : %s', $exception->getMessage()),
                previous: $exception,
            );
        }

        return $this->parser->parse($xml);
    }
}
