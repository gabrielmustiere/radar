<?php

declare(strict_types=1);

namespace App\Service\Preview\Extractor;

use App\Service\Preview\HtmlPage;
use App\Service\Preview\LinkPreview;
use App\Service\Preview\MetadataValue;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 300)]
final class OpenGraphExtractor implements MetadataExtractor
{
    #[\Override]
    public function extract(string $url, ?HtmlPage $page): LinkPreview
    {
        if (null === $page) {
            return new LinkPreview();
        }

        return new LinkPreview(
            title: $page->meta('og:title'),
            description: $page->meta('og:description'),
            imageUrl: $page->absoluteUrl($page->meta('og:image:secure_url', 'og:image')),
            siteName: $page->meta('og:site_name'),
            publishedAt: MetadataValue::date($page->meta('article:published_time')),
        );
    }
}
