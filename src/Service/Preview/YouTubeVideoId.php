<?php

declare(strict_types=1);

namespace App\Service\Preview;

final class YouTubeVideoId
{
    private const array HOSTS = ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'];

    /** Identifiant d'une vidéo YouTube : `watch?v=`, `youtu.be/`, `shorts/`, `embed/` ou `live/`. */
    public static function fromUrl(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = str_starts_with($host, 'www.') ? substr($host, offset: 4) : $host;
        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = [];
        $matches = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $id = match (true) {
            'youtu.be' === $host => explode('/', ltrim($path, characters: '/'))[0],
            !in_array($host, self::HOSTS, strict: true) => null,
            '/watch' === rtrim($path, characters: '/') => $query['v'] ?? null,
            1 === preg_match('#^/(?:shorts|embed|live)/([^/]+)#', $path, $matches) => $matches[1] ?? null,
            default => null,
        };

        return is_string($id) && 1 === preg_match('/^[\w-]{11}$/', $id) ? $id : null;
    }
}
