<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Link;
use App\Enum\Type\PreviewStatus;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Carte dont l'aperçu est en cours de construction (worker Messenger) : elle se rafraîchit seule
 * jusqu'à ce qu'il soit prêt, puis s'arrête. Plafonnée si aucun worker ne tourne.
 */
#[AsLiveComponent]
final class PendingLinkCard
{
    use DefaultActionTrait;

    public const int MAX_REFRESHES = 20;

    #[LiveProp]
    public ?Link $link = null;

    #[LiveProp]
    public int $refreshes = 0;

    #[LiveAction]
    public function refresh(): void
    {
        ++$this->refreshes;
    }

    public function isPolling(): bool
    {
        return PreviewStatus::Pending === $this->link?->getPreviewStatus() && $this->refreshes < self::MAX_REFRESHES;
    }
}
