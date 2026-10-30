<?php

declare(strict_types=1);

namespace App\Feed;

final readonly class FeedItem
{
    private const int MAX_LENGTH = 2048;

    public function __construct(
        public string $key,
        public string $url,
        public ?\DateTimeImmutable $publishedAt = null,
    ) {}

    /** `null` pour un article sans lien absolu http(s) ou trop long pour devenir un lien Radar. */
    public static function tryFrom(?string $key, ?string $url, ?string $publishedAt = null): ?self
    {
        if (null === $url || mb_strlen($url) > self::MAX_LENGTH) {
            return null;
        }

        if (
            !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], strict: true)
            || '' === (string) parse_url($url, PHP_URL_HOST)
        ) {
            return null;
        }

        $key ??= $url;

        if (mb_strlen($key) > self::MAX_LENGTH) {
            return null;
        }

        $date = null === $publishedAt ? false : date_create_immutable($publishedAt);

        return new self($key, $url, false === $date ? null : $date);
    }

    /** Un article sans date n'est jamais considéré comme récent. */
    public function isPublishedSince(\DateTimeImmutable $since): bool
    {
        return null !== $this->publishedAt && $this->publishedAt >= $since;
    }
}
