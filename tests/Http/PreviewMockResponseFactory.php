<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Tests\Service\Preview\TestImage;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Réponses des sources en environnement de test : aucun appel réseau réel.
 * `/article…` expose un aperçu OpenGraph, `.png` est une image, `/feed/…` est un flux, tout le reste répond 404.
 */
final class PreviewMockResponseFactory
{
    public function __invoke(string $_method, string $url): ResponseInterface
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (str_ends_with($path, '.png')) {
            return new MockResponse(TestImage::png(), ['response_headers' => ['content-type' => 'image/png']]);
        }

        if (str_starts_with($path, '/feed/')) {
            return $this->feed($path, (string) parse_url($url, PHP_URL_QUERY));
        }

        if (str_starts_with($path, '/article')) {
            $html = <<<'HTML'
                <html><head>
                <title>Titre de repli</title>
                <meta property="og:title" content="Un article de test">
                <meta property="og:description" content="Ce qu'il faut savoir sur les tests.">
                <meta property="og:image" content="/cover.png">
                <meta property="og:site_name" content="Example">
                </head><body></body></html>
                HTML;

            return new MockResponse($html, ['response_headers' => ['content-type' => 'text/html; charset=utf-8']]);
        }

        return new MockResponse('', ['http_code' => 404]);
    }

    /**
     * `/feed/journal?run=x` : deux articles propres à `x` (servis par `/article…`), le plus récent en tête :
     * le n° 2 paru il y a 2 h, le n° 1 il y a 30 h.
     */
    private function feed(string $path, string $query): MockResponse
    {
        $parameters = [];
        parse_str($query, $parameters);
        $run = $parameters['run'] ?? 'run';
        $run = is_string($run) ? $run : 'run';
        $xml = match ($path) {
            '/feed/symfony' => (string) file_get_contents(__DIR__ . '/../Fixtures/feed/symfony-blog.xml'),
            '/feed/journal' => $this->journal($run),
            '/feed/broken' => '<rss><channel>',
            default => null,
        };

        if (null === $xml) {
            return new MockResponse('<html><body>Pas un flux</body></html>', ['response_headers' => [
                'content-type' => 'text/html',
            ]]);
        }

        return new MockResponse($xml, ['response_headers' => ['content-type' => 'application/rss+xml']]);
    }

    private function journal(string $run): string
    {
        $items = '';
        foreach ([2 => 2, 1 => 30] as $number => $hoursAgo) {
            $items .= sprintf(
                '<item><link>https://example.com/article-%1$s-%2$d</link><guid>%1$s-%2$d</guid><pubDate>%3$s</pubDate></item>',
                $run,
                $number,
                date(DATE_RSS, time() - ($hoursAgo * 3600)),
            );
        }

        return sprintf('<rss version="2.0"><channel><title>Journal %s</title>%s</channel></rss>', $run, $items);
    }
}
