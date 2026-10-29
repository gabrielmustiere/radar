<?php

declare(strict_types=1);

namespace App\Service\Preview;

final class LinkPreviewParser
{
    private const int TITLE_MAX_LENGTH = 255;
    private const int DESCRIPTION_MAX_LENGTH = 500;
    private const int URL_MAX_LENGTH = 2048;

    public function parse(string $html, string $baseUrl): LinkPreview
    {
        $metadata = HtmlMetadata::fromHtml($html);
        $image = $metadata->first('og:image', 'twitter:image');

        return new LinkPreview(
            $this->truncate(
                $metadata->first('og:title', 'twitter:title') ?? $metadata->title(),
                self::TITLE_MAX_LENGTH,
            ),
            $this->truncate(
                $metadata->first('og:description', 'twitter:description', 'description'),
                self::DESCRIPTION_MAX_LENGTH,
            ),
            null === $image ? null : $this->absoluteUrl($image, $baseUrl),
            $metadata->first('og:site_name'),
        );
    }

    private function truncate(?string $value, int $length): ?string
    {
        return null === $value ? null : mb_substr($value, start: 0, length: $length);
    }

    private function absoluteUrl(string $url, string $baseUrl): ?string
    {
        $resolved = \Uri\WhatWg\Url::parse($url, \Uri\WhatWg\Url::parse($baseUrl));
        if (null === $resolved || !in_array($resolved->getScheme(), ['http', 'https'], strict: true)) {
            return null;
        }

        $absolute = $resolved->toAsciiString();

        return strlen($absolute) > self::URL_MAX_LENGTH ? null : $absolute;
    }
}
