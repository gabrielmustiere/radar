<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Link;
use App\Entity\LinkOpening;
use App\Entity\User;
use App\Repository\LinkOpeningRepository;
use Psr\Clock\ClockInterface;

final readonly class LinkOpeningRecorder
{
    public function __construct(
        private LinkOpeningRepository $openingRepository,
        private ClockInterface $clock,
    ) {}

    /** L'âge se compte en jours révolus depuis la date d'origine du lien (R10). */
    public function record(Link $link, User $member): LinkOpening
    {
        $now = $this->clock->now();
        $ageDays = $link->getPostedAt() > $now ? 0 : (int) $link->getPostedAt()->diff($now)->days;

        $opening = new LinkOpening($link, $member, $now, $ageDays);
        $this->openingRepository->save($opening);

        return $opening;
    }
}
