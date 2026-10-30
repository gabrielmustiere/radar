<?php

declare(strict_types=1);

namespace App\Service\Preview\Extractor;

use App\Service\Preview\HtmlPage;
use App\Service\Preview\LinkPreview;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Une source de métadonnées. La priorité du tag (`#[AsTaggedItem]`) fixe sa place dans la cascade :
 * pour chaque champ, la source la plus prioritaire qui le fournit l'emporte.
 */
#[AutoconfigureTag]
interface MetadataExtractor
{
    /** `$page` est `null` quand la page n'a pas été téléchargée ou n'est pas du HTML. */
    public function extract(string $url, ?HtmlPage $page): LinkPreview;
}
