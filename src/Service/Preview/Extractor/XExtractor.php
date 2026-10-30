<?php

declare(strict_types=1);

namespace App\Service\Preview\Extractor;

use App\Enum\Type\SourceType;
use App\Service\LinkUrlAnalyzer;
use App\Service\Preview\HtmlPage;
use App\Service\Preview\HtmlParser;
use App\Service\Preview\JsonFetcher;
use App\Service\Preview\LinkPreview;
use App\Service\Preview\MetadataValue;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Post X par l'oEmbed officiel. Son `html` n'est jamais rendu : on n'en garde que le texte du post.
 */
#[AsTaggedItem(priority: 500)]
final readonly class XExtractor implements MetadataExtractor
{
    public function __construct(
        private LinkUrlAnalyzer $urlAnalyzer,
        private JsonFetcher $jsonFetcher,
    ) {}

    #[\Override]
    public function extract(string $url, ?HtmlPage $page): LinkPreview
    {
        if (SourceType::X !== $this->urlAnalyzer->sourceType($url) || !str_contains($url, '/status/')) {
            return new LinkPreview();
        }

        $data = $this->jsonFetcher->fetch(
            'https://publish.twitter.com/oembed?omit_script=true&dnt=true&url=' . rawurlencode($url),
        ) ?? [];
        $author = MetadataValue::text($data['author_name'] ?? null);

        return new LinkPreview(
            title: null === $author ? null : $author . ' sur X',
            description: $this->postText($data['html'] ?? null),
            siteName: 'X',
            author: $author,
        );
    }

    private function postText(mixed $html): ?string
    {
        if (!is_string($html)) {
            return null;
        }

        return MetadataValue::text(HtmlParser::parse($html)->querySelector('blockquote p')?->textContent);
    }
}
