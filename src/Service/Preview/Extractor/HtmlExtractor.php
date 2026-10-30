<?php

declare(strict_types=1);

namespace App\Service\Preview\Extractor;

use App\Service\Preview\HtmlPage;
use App\Service\Preview\LinkPreview;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Dernier recours : `<title>`, `meta description` et favicon.
 */
#[AsTaggedItem(priority: 100)]
final class HtmlExtractor implements MetadataExtractor
{
    private const array ICON_RELS = ['icon', 'apple-touch-icon'];

    #[\Override]
    public function extract(string $url, ?HtmlPage $page): LinkPreview
    {
        if (null === $page) {
            return new LinkPreview();
        }

        return new LinkPreview(
            title: $page->title(),
            description: $page->meta('description'),
            faviconUrl: $this->favicon($page),
        );
    }

    /** Première icône dans un format raster : l'ICO ne se décode pas, un SVG servi par Radar serait exécutable. */
    private function favicon(HtmlPage $page): ?string
    {
        foreach (self::ICON_RELS as $rel) {
            foreach ($page->links($rel) as $link) {
                $extension = strtolower(pathinfo((string) parse_url($link['href'], PHP_URL_PATH), PATHINFO_EXTENSION));
                if (
                    !in_array($extension, ['ico', 'svg'], strict: true)
                    && !in_array(
                        $link['type'],
                        ['image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml'],
                        strict: true,
                    )
                ) {
                    return $link['href'];
                }
            }
        }

        return null;
    }
}
