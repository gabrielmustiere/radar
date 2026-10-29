<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\Type\SourceType;

/**
 * Lecture d'une adresse de lien : sa forme canonique (deux adresses de même forme désignent le même lien, R6)
 * et son type de source.
 */
final class LinkUrlAnalyzer
{
    private const array IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'avif'];
    private const array TRACKING_PARAMETERS = ['fbclid', 'gclid', 'mc_cid', 'mc_eid', 'si'];

    public function normalize(string $url): string
    {
        $parsed = \Uri\WhatWg\Url::parse(trim($url));
        if (null === $parsed) {
            return trim($url);
        }

        $host = strtolower((string) $parsed->getAsciiHost());
        $host = str_starts_with($host, 'www.') ? substr($host, offset: 4) : $host;
        $path = rtrim($parsed->getPath(), characters: '/');
        $query = $this->keptParameters((string) $parsed->getQuery());

        if ('youtu.be' === $host && '' !== $path) {
            $query = ['v=' . ltrim($path, characters: '/'), ...$query];
            [$host, $path] = ['youtube.com', '/watch'];
        }

        $port = $parsed->getPort();
        $port = null === $port ? '' : ':' . $port;

        return 'https://' . $host . $port . $path . ([] === $query ? '' : '?' . implode('&', $query));
    }

    /** @return list<string> paires `clé=valeur` conservées, dans leur ordre d'origine */
    private function keptParameters(string $query): array
    {
        $kept = [];
        foreach (explode('&', $query) as $pair) {
            $key = strtolower(explode('=', $pair, limit: 2)[0]);
            if (
                '' === $key
                || str_starts_with($key, 'utm_')
                || in_array($key, self::TRACKING_PARAMETERS, strict: true)
            ) {
                continue;
            }

            $kept[] = $pair;
        }

        return $kept;
    }

    public function sourceType(string $url): SourceType
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = str_starts_with($host, 'www.') ? substr($host, offset: 4) : $host;
        $path = (string) parse_url($url, PHP_URL_PATH);

        return match (true) {
            'github.com' === $host => SourceType::GitHub,
            in_array($host, ['youtube.com', 'm.youtube.com', 'youtu.be'], strict: true) => SourceType::YouTube,
            in_array($host, ['x.com', 'twitter.com', 'mobile.twitter.com'], strict: true) => SourceType::X,
            in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, strict: true)
                => SourceType::Image,
            default => SourceType::Article,
        };
    }
}
