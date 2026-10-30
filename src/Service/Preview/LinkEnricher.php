<?php

declare(strict_types=1);

namespace App\Service\Preview;

use App\Entity\Link;
use Psr\Clock\ClockInterface;

/**
 * Snapshot d'aperçu d'un lien : métadonnées de la source, visuels conservés par Radar, date de la tentative.
 */
final readonly class LinkEnricher
{
    public function __construct(
        private MetadataResolver $resolver,
        private PreviewImageStore $imageStore,
        private ClockInterface $clock,
    ) {}

    public function enrich(Link $link): void
    {
        $preview = $this->resolver->resolve($link->getUrl());
        if (null === $preview) {
            $link->markPreviewUnavailable($this->clock->now());

            return;
        }

        // Un rafraîchissement partiel (service d'aperçu ou image momentanément indisponibles) n'efface rien de ce qui était connu.
        $previous = $link->previewSnapshot();
        $preview = $preview->withFallback($previous);
        $imagePath = $this->imageStore->storePreview($preview->imageUrl);
        if (null === $imagePath && $preview->imageUrl === $previous->imageUrl) {
            $imagePath = $link->getImagePath();
        }

        $link->applyPreview(
            $preview,
            $imagePath,
            $this->imageStore->storeFavicon($preview->faviconUrl) ?? $link->getFaviconPath(),
            $this->clock->now(),
        );
    }
}
