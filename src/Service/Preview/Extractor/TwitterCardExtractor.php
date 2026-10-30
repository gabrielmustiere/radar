<?php

declare(strict_types=1);

namespace App\Service\Preview\Extractor;

use App\Service\Preview\HtmlPage;
use App\Service\Preview\LinkPreview;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 200)]
final class TwitterCardExtractor implements MetadataExtractor
{
    #[\Override]
    public function extract(string $url, ?HtmlPage $page): LinkPreview
    {
        if (null === $page) {
            return new LinkPreview();
        }

        return new LinkPreview(
            title: $page->meta('twitter:title'),
            description: $page->meta('twitter:description'),
            imageUrl: $page->absoluteUrl($page->meta('twitter:image', 'twitter:image:src')),
        );
    }
}
