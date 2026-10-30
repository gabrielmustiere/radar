<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Tests\Service\Preview\TestImage;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Réponses des sources en environnement de test : aucun appel réseau réel.
 * `/article…` expose un aperçu OpenGraph, `.png` est une image, tout le reste répond 404.
 */
final class PreviewMockResponseFactory
{
    public function __invoke(string $_method, string $url): ResponseInterface
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (str_ends_with($path, '.png')) {
            return new MockResponse(TestImage::png(), ['response_headers' => ['content-type' => 'image/png']]);
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
}
