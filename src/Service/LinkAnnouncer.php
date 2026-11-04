<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Entity\Link;
use App\Service\Preview\LinkBannerStore;
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
        $this->chatter->send(new ChatMessage($this->text($link)));
        $link->markAnnounced($this->clock->now());
    }

    /** Retire ce qu'une annonce a rendu public. */
    public function forget(Link $link): void
    {
        $this->bannerStore->remove($link);
    }

    private function text(Link $link): string
    {
        $categories = implode(
            ', ',
            $link
                ->getCategories()
                ->map(fn(Category $category): string => $this->safeText($category->getName()))
                ->toArray(),
        );
        $cardUrl = $this->urlGenerator->generate(
            'app_link_show',
            ['id' => $link->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $url = $this->safeUrl($link->getUrl());
        $source = $link->getSource();
        $title = $link->getTitle();
        $lines = [
            sprintf('*%s*', null === $title ? $url : $this->safeText($title)),
            sprintf(
                '%s · posté par %s%s',
                $categories,
                $this->safeText($link->getPostedBy()->getDisplayName()),
                null === $source ? '' : ' via ' . $this->safeText($source->getName()),
            ),
        ];
        $context = $link->getContext();
        if (null !== $context) {
            $lines[] = sprintf('_« %s »_', $this->safeText($context));
        }
        $lines[] = sprintf('<%s|Ouvrir le lien> · <%s|Voir dans Radar>', $url, $cardUrl);

        return implode("\n", $lines);
    }

    /** Le titre vient du site cible : `<users/all>` notifierait tout l'espace, `<url|texte>` déguiserait un lien. */
    private function safeText(string $text): string
    {
        return strtr($text, ['<' => '‹', '>' => '›']);
    }

    /** Une URL ne peut pas porter de sosie : `|` et `>` la feraient sortir de la balise qui l'entoure. */
    private function safeUrl(string $url): string
    {
        return strtr($url, ['<' => '%3C', '>' => '%3E', '|' => '%7C']);
    }
}
