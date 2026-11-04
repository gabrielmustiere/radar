<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Entity\Link;
use Symfony\Component\Notifier\Bridge\GoogleChat\GoogleChatOptions;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Carte d'annonce Google Chat d'un lien. Le titre, la description et le contexte viennent de l'extérieur, et Chat
 * interprète le HTML de la carte : seuls le bandeau et les boutons construits ici doivent y porter une adresse.
 */
final readonly class LinkAnnouncementCard
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @param ?string $bannerName sans bandeau, la carte passe à sa version compacte */
    public function message(Link $link, ?string $bannerName): ChatMessage
    {
        $title = $this->title($link);
        $subtitle = $this->subtitle($link);
        $byline = $this->byline($link);
        // Le transport envoie les options seules : le sujet ne sert qu'aux tests et aux logs.
        $fallbackText = implode(' · ', [$title, $subtitle, $byline]);
        $options = new GoogleChatOptions(['fallbackText' => $fallbackText]);

        return new ChatMessage($fallbackText, $options->cardV2([
            'cardId' => sprintf('link-%d', (int) $link->getId()),
            'card' => [
                'header' => [
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'imageUrl' => $this->url('app_page') . 'logo.png',
                    'imageType' => 'SQUARE',
                ],
                'sections' => [['widgets' => $this->widgets($link, $title, $byline, $bannerName)]],
            ],
        ]));
    }

    /** @return list<array<string, mixed>> */
    private function widgets(Link $link, string $title, string $byline, ?string $bannerName): array
    {
        $widgets = [];
        if (null !== $bannerName) {
            $widgets[] = ['image' => [
                'imageUrl' => $this->url('app_announcement_banner', ['name' => $bannerName]),
                'altText' => $title,
            ]];
        }

        $summary = $link->getContext() ?? $link->getDescription();
        if (null !== $summary) {
            $widgets[] = ['textParagraph' => ['text' => $this->safeText($summary)]];
        }

        $widgets[] = ['textParagraph' => ['text' => $byline]];
        $widgets[] = [
            'buttonList' => ['buttons' => [
                $this->button('Ouvrir le lien', $this->safeUrl($link->getUrl())),
                $this->button('Voir dans Radar', $this->url('app_link_show', ['id' => $link->getId()])),
            ]],
        ];

        return $widgets;
    }

    /** @return array<string, mixed> */
    private function button(string $text, string $url): array
    {
        return ['text' => $text, 'onClick' => ['openLink' => ['url' => $url]]];
    }

    private function title(Link $link): string
    {
        $title = $link->getTitle();

        return null === $title ? $this->safeUrl($link->getUrl()) : $this->safeText($title);
    }

    private function subtitle(Link $link): string
    {
        $categories = $link
            ->getCategories()
            ->map(fn(Category $category): string => $this->safeText($category->getName()))
            ->toArray();

        return $link->getSourceType()->label() . ' · ' . implode(', ', $categories);
    }

    private function byline(Link $link): string
    {
        $source = $link->getSource();

        return null === $source
            ? 'Posté par ' . $this->safeText($link->getPostedBy()->getDisplayName())
            : 'Via ' . $this->safeText($source->getName());
    }

    /** @param array<string, mixed> $parameters */
    private function url(string $route, array $parameters = []): string
    {
        return $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /** `<users/all>` notifierait tout l'espace, `<a href=…>` déguiserait un lien. */
    private function safeText(string $text): string
    {
        return strtr($text, ['<' => '‹', '>' => '›']);
    }

    /** Une URL ne peut pas porter de sosie, or elle s'affiche en titre de repli et part dans un bouton. */
    private function safeUrl(string $url): string
    {
        return strtr($url, ['<' => '%3C', '>' => '%3E', '|' => '%7C']);
    }
}
