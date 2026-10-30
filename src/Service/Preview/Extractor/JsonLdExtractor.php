<?php

declare(strict_types=1);

namespace App\Service\Preview\Extractor;

use App\Service\Preview\HtmlPage;
use App\Service\Preview\JsonLdAuthor;
use App\Service\Preview\JsonLdGraph;
use App\Service\Preview\LinkPreview;
use App\Service\Preview\MetadataValue;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Données schema.org : surtout l'auteur et la date de publication, que les balises `<meta>` donnent rarement.
 */
#[AsTaggedItem(priority: 150)]
final class JsonLdExtractor implements MetadataExtractor
{
    /** Types de nœud décrivant le contenu lui-même, du plus au moins spécifique. */
    private const array CONTENT_TYPES = [
        'Article',
        'NewsArticle',
        'BlogPosting',
        'TechArticle',
        'ScholarlyArticle',
        'Report',
        'SocialMediaPosting',
        'VideoObject',
        'WebPage',
    ];

    #[\Override]
    public function extract(string $url, ?HtmlPage $page): LinkPreview
    {
        if (null === $page) {
            return new LinkPreview();
        }

        $graph = JsonLdGraph::fromBlocks($page->jsonLdBlocks());
        $node = $graph->firstOfType(self::CONTENT_TYPES);
        if (null === $node) {
            return new LinkPreview();
        }

        return new LinkPreview(
            title: MetadataValue::text($node['headline'] ?? null) ?? MetadataValue::text($node['name'] ?? null),
            description: MetadataValue::text($node['description'] ?? null),
            imageUrl: $page->absoluteUrl($this->imageUrl($node['image'] ?? null)),
            author: JsonLdAuthor::names($node['author'] ?? null, $graph),
            publishedAt: MetadataValue::date($node['datePublished'] ?? null),
        );
    }

    private function imageUrl(mixed $image): ?string
    {
        if (is_array($image)) {
            return $this->imageUrl(array_is_list($image) ? $image[0] ?? null : $image['url'] ?? null);
        }

        return MetadataValue::text($image);
    }
}
