<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Entity\Link;
use App\Enum\Type\SourceType;
use App\Twig\LinkExtension;
use Symfony\Component\Notifier\Bridge\GoogleChat\GoogleChatOptions;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Carte d'annonce Google Chat d'un lien. Le titre, la description et le contexte viennent de l'extérieur, et Chat
 * interprète le HTML de la carte : seuls les boutons et les balises construits ici doivent y porter une adresse ou du
 * balisage.
 *
 * @phpstan-type Style array{icon: string, color: array<string, float|int>, text: string, action: string}
 */
final readonly class LinkAnnouncementCard
{
    private const int DESCRIPTION_MAX_LENGTH = 160;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private LinkExtension $linkExtension,
    ) {}

    public function message(Link $link): ChatMessage
    {
        $style = $this->style($link);
        $home = $this->urlGenerator->generate('app_page', referenceType: UrlGeneratorInterface::ABSOLUTE_URL);
        $title = $this->safeText($this->linkExtension->title($link));
        $subtitle = $this->subtitle($link);
        // Le transport envoie les options seules : le sujet ne sert qu'aux tests et aux logs.
        $fallbackText = implode(' · ', [$title, $subtitle, $this->byline($link)]);
        $options = new GoogleChatOptions(['fallbackText' => $fallbackText]);

        return new ChatMessage($fallbackText, $options->cardV2([
            'cardId' => sprintf('link-%d', (int) $link->getId()),
            'card' => [
                'header' => [
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'imageUrl' => sprintf('%schat/icons/%s.png', $home, $style['icon']),
                    'imageType' => 'SQUARE',
                    'imageAltText' => null === $link->getSource() ? $link->getSourceType()->label() : 'Source suivie',
                ],
                'sections' => [['widgets' => $this->widgets($link, $title, $style)]],
            ],
        ]));
    }

    /**
     * @param Style $style
     *
     * @return list<array<string, mixed>>
     */
    private function widgets(Link $link, string $title, array $style): array
    {
        $radarUrl = $this->urlGenerator->generate(
            'app_link_show',
            ['id' => $link->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $widgets = [];

        $description = $this->description($link, $title);
        if (null !== $description) {
            $widgets[] = ['textParagraph' => ['text' => $description]];
        }

        $widgets[] = ['textParagraph' => ['text' => $this->why($link, $style['text'])]];
        $widgets[] = [
            'buttonList' => [
                'buttons' => [
                    [
                        'text' => $style['action'],
                        'color' => $style['color'],
                        'onClick' => ['openLink' => ['url' => $this->safeUrl($link->getUrl())]],
                    ],
                    [
                        'text' => 'Voir dans Radar',
                        'type' => 'BORDERLESS',
                        'onClick' => ['openLink' => ['url' => $radarUrl]],
                    ],
                ],
            ],
        ];

        return $widgets;
    }

    /**
     * `icon` nomme le fichier de `public/chat/icons/`. Google lit `color` en RGBA de 0 à 1.
     *
     * @return Style
     */
    private function style(Link $link): array
    {
        if (null !== $link->getSource()) {
            return [
                'icon' => 'rss',
                'color' => ['red' => 0.761, 'green' => 0.255, 'blue' => 0.047, 'alpha' => 1],
                'text' => '#C2410C',
                'action' => 'Lire l\'article',
            ];
        }

        return match ($link->getSourceType()) {
            SourceType::YouTube => [
                'icon' => 'youtube',
                'color' => ['red' => 0.882, 'green' => 0.114, 'blue' => 0.282, 'alpha' => 1],
                'text' => '#BE123C',
                'action' => 'Regarder la vidéo',
            ],
            SourceType::GitHub => [
                'icon' => 'github',
                'color' => ['red' => 0.486, 'green' => 0.227, 'blue' => 0.929, 'alpha' => 1],
                'text' => '#6D28D9',
                'action' => 'Voir le dépôt',
            ],
            SourceType::X => [
                'icon' => 'x',
                'color' => ['red' => 0.012, 'green' => 0.412, 'blue' => 0.631, 'alpha' => 1],
                'text' => '#0369A1',
                'action' => 'Voir sur X',
            ],
            SourceType::Article => [
                'icon' => 'article',
                'color' => ['red' => 0.059, 'green' => 0.463, 'blue' => 0.431, 'alpha' => 1],
                'text' => '#0F766E',
                'action' => 'Lire l\'article',
            ],
            SourceType::Image => [
                'icon' => 'image',
                'color' => ['red' => 0.09, 'green' => 0.094, 'blue' => 0.11, 'alpha' => 1],
                'text' => '#17181C',
                'action' => 'Voir l\'image',
            ],
        };
    }

    private function subtitle(Link $link): string
    {
        $type = $link->getSourceType();
        $categories = $link
            ->getCategories()
            ->map(fn(Category $category): string => $this->safeText($category->getName()))
            ->toArray();
        $domain = SourceType::Article === $type ? $this->safeText($this->linkExtension->host($link->getUrl())) : '';

        return implode(' · ', array_filter(
            [$type->label(), $domain, implode(', ', $categories)],
            static fn(string $part): bool => '' !== $part,
        ));
    }

    /** Une description n'a d'intérêt que pour un dépôt ou un article, et si elle ne répète pas le titre. */
    private function description(Link $link, string $title): ?string
    {
        $description = $link->getDescription();
        if (
            null === $description
            || !in_array($link->getSourceType(), [SourceType::GitHub, SourceType::Article], strict: true)
        ) {
            return null;
        }

        $description = $this->safeText($description);
        $comparable = static fn(string $text): string => mb_strtolower((string) preg_replace(
            '/\s+/u',
            replacement: '',
            subject: $text,
        ));
        if ($comparable($description) === $comparable($title)) {
            return null;
        }

        return mb_strlen($description) > self::DESCRIPTION_MAX_LENGTH
            ? rtrim(mb_substr($description, start: 0, length: self::DESCRIPTION_MAX_LENGTH - 1)) . '…'
            : $description;
    }

    private function why(Link $link, string $color): string
    {
        $context = $link->getContext();
        if (null === $context || null !== $link->getSource()) {
            return sprintf('<font color="%s">%s</font>', $color, $this->byline($link));
        }

        return sprintf(
            '<b><font color="%s">%s :</font></b> <i>%s</i>',
            $color,
            $this->safeText($link->getPostedBy()->getDisplayName()),
            $this->safeText($context),
        );
    }

    private function byline(Link $link): string
    {
        $source = $link->getSource();

        return null === $source
            ? 'Posté par ' . $this->safeText($link->getPostedBy()->getDisplayName())
            : 'Via le flux ' . $this->safeText($source->getName());
    }

    /** `<users/all>` notifierait tout l'espace, `<a href=…>` déguiserait un lien. */
    private function safeText(string $text): string
    {
        return strtr($text, ['<' => '‹', '>' => '›']);
    }

    /** Une URL ne peut pas porter de sosie, or elle part dans un bouton. */
    private function safeUrl(string $url): string
    {
        return strtr($url, ['<' => '%3C', '>' => '%3E', '|' => '%7C']);
    }
}
