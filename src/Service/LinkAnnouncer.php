<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Link;
use App\Service\Preview\LinkBannerStore;
use Psr\Clock\ClockInterface;
use Symfony\Component\Notifier\ChatterInterface;

/**
 * Annonce d'un nouveau lien dans l'espace Google Chat : une seule fois, jamais pour un lien repris (R5).
 */
final readonly class LinkAnnouncer
{
    public function __construct(
        private ChatterInterface $chatter,
        private LinkAnnouncementCard $card,
        private ClockInterface $clock,
        private LinkBannerStore $bannerStore,
    ) {}

    /** Le marquage est enregistré au flush de l'appelant, avec l'aperçu. */
    public function announce(Link $link): void
    {
        if ($link->isSeeded() || null !== $link->getAnnouncedAt()) {
            return;
        }

        $this->reannounce($link);
    }

    /** Annonce décidée par un admin, sans les gardes de l'annonce automatique. Enregistrée au flush de l'appelant. */
    public function reannounce(Link $link): void
    {
        $this->chatter->send($this->card->message($link));
        $link->markAnnounced($this->clock->now());
    }

    /** Retire ce qu'une annonce a rendu public. */
    public function forget(Link $link): void
    {
        $this->bannerStore->remove($link);
    }
}
