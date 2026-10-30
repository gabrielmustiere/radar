<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Entity\Link;
use Psr\Clock\ClockInterface;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Annonce d'un nouveau lien dans l'espace Google Chat : une seule fois, jamais pour un lien repris (R5).
 */
final readonly class LinkAnnouncer
{
    public function __construct(
        private ChatterInterface $chatter,
        private UrlGeneratorInterface $urlGenerator,
        private ClockInterface $clock,
    ) {}

    /** Le marquage est enregistré au flush de l'appelant, avec l'aperçu. */
    public function announce(Link $link): void
    {
        if ($link->isSeeded() || null !== $link->getAnnouncedAt()) {
            return;
        }

        $this->chatter->send(new ChatMessage($this->text($link)));
        $link->markAnnounced($this->clock->now());
    }

    private function text(Link $link): string
    {
        $categories = implode(
            ', ',
            $link
                ->getCategories()
                ->map(static fn(Category $category): string => $category->getName())
                ->toArray(),
        );
        $cardUrl = $this->urlGenerator->generate(
            'app_link_show',
            ['id' => $link->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $source = $link->getSource();
        $lines = [
            sprintf('*%s*', $link->getTitle() ?? $link->getUrl()),
            sprintf(
                '%s · posté par %s%s',
                $categories,
                $link->getPostedBy()->getDisplayName(),
                null === $source ? '' : ' via ' . $source->getName(),
            ),
        ];
        if (null !== $link->getContext()) {
            $lines[] = sprintf('_« %s »_', $link->getContext());
        }
        $lines[] = sprintf('<%s|Ouvrir le lien> · <%s|Voir dans Radar>', $link->getUrl(), $cardUrl);

        return implode("\n", $lines);
    }
}
