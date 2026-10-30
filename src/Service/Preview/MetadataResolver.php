<?php

declare(strict_types=1);

namespace App\Service\Preview;

use App\Enum\Type\SourceType;
use App\Service\LinkUrlAnalyzer;
use App\Service\Preview\Extractor\MetadataExtractor;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Aperçu d'un lien : la page est téléchargée une fois, chaque source en tire ce qu'elle sait, et pour chaque champ
 * la source la plus prioritaire qui le fournit l'emporte.
 */
final readonly class MetadataResolver
{
    private const int TITLE_MAX_LENGTH = 100;
    private const int DESCRIPTION_MAX_LENGTH = 250;
    private const int NAME_MAX_LENGTH = 255;

    /** @param iterable<MetadataExtractor> $extractors du plus au moins prioritaire */
    public function __construct(
        private PageFetcher $pageFetcher,
        private LinkUrlAnalyzer $urlAnalyzer,
        private TextTruncator $truncator,
        #[AutowireIterator(MetadataExtractor::class)]
        private iterable $extractors,
    ) {}

    public function resolve(string $url): ?LinkPreview
    {
        // Sans connexion, X ne sert qu'une page générique dont les balises pollueraient l'aperçu de son oEmbed.
        $page = SourceType::X === $this->urlAnalyzer->sourceType($url) ? null : $this->pageFetcher->fetch($url);
        if ($page instanceof LinkPreview) {
            return $page;
        }

        $preview = new LinkPreview();
        foreach ($this->extractors as $extractor) {
            $preview = $preview->withFallback($extractor->extract($url, $page));
        }

        $preview = $preview->withText(
            $this->truncator->truncate($preview->title, self::TITLE_MAX_LENGTH),
            $this->truncator->truncate($preview->description, self::DESCRIPTION_MAX_LENGTH),
            $this->truncator->truncate($preview->siteName, self::NAME_MAX_LENGTH),
            $this->truncator->truncate($preview->author, self::NAME_MAX_LENGTH),
        );

        return $preview->isEmpty() ? null : $preview;
    }
}
