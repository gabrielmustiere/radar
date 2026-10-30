<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Reconstruction de l'aperçu d'un lien existant : contrairement à l'ajout, elle n'est jamais annoncée (R5).
 */
final readonly class RefreshLinkPreview
{
    public function __construct(
        public int $linkId,
    ) {}
}
