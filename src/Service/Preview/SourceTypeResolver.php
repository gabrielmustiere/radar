<?php

declare(strict_types=1);

namespace App\Service\Preview;

use App\Enum\Type\SourceType;

final class SourceTypeResolver
{
    private const array IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'avif'];

    public function resolve(string $url): SourceType
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
