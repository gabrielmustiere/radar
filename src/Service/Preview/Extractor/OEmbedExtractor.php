<?php

declare(strict_types=1);

namespace App\Service\Preview\Extractor;

use App\Service\Preview\HtmlPage;
use App\Service\Preview\JsonFetcher;
use App\Service\Preview\LinkPreview;
use App\Service\Preview\MetadataValue;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * oEmbed découvert dans la page, limité aux fournisseurs connus. YouTube et X ont leur adaptateur dédié.
 * Le `html` d'oEmbed n'est jamais repris : seuls des champs texte et l'URL de la miniature.
 */
#[AsTaggedItem(priority: 400)]
final readonly class OEmbedExtractor implements MetadataExtractor
{
    private const array PROVIDERS = ['vimeo.com', 'speakerdeck.com'];

    public function __construct(
        private JsonFetcher $jsonFetcher,
    ) {}

    #[\Override]
    public function extract(string $url, ?HtmlPage $page): LinkPreview
    {
        $endpoint = $this->endpoint($page);
        if (null === $endpoint) {
            return new LinkPreview();
        }

        $data = $this->jsonFetcher->fetch($endpoint) ?? [];

        return new LinkPreview(
            title: MetadataValue::text($data['title'] ?? null),
            imageUrl: $page?->absoluteUrl(MetadataValue::text($data['thumbnail_url'] ?? null)),
            siteName: MetadataValue::text($data['provider_name'] ?? null),
            author: MetadataValue::text($data['author_name'] ?? null),
        );
    }

    private function endpoint(?HtmlPage $page): ?string
    {
        foreach ($page?->links('alternate') ?? [] as $link) {
            if ('application/json+oembed' === $link['type'] && $this->isKnownProvider($link['href'])) {
                return $link['href'];
            }
        }

        return null;
    }

    private function isKnownProvider(string $endpoint): bool
    {
        $host = strtolower((string) parse_url($endpoint, PHP_URL_HOST));
        $host = str_starts_with($host, 'www.') ? substr($host, offset: 4) : $host;

        return 'https' === parse_url($endpoint, PHP_URL_SCHEME) && in_array($host, self::PROVIDERS, strict: true);
    }
}
