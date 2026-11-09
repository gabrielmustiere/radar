<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Link;
use App\Enum\Type\SourceType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;

final class LinkAnnouncementCardTest extends LinkAnnouncementCardTestCase
{
    /** @return iterable<string, array{SourceType, array{icon: string, color: string, text: string, action: string, label: string}}> */
    public static function provideTypes(): iterable
    {
        yield 'YouTube' => [
            SourceType::YouTube,
            [
                'icon' => 'youtube',
                'color' => '{"red":0.745,"green":0.071,"blue":0.235,"alpha":1}',
                'text' => '#BE123C',
                'action' => 'Regarder la vidéo',
                'label' => 'Vidéo YouTube',
            ],
        ];
        yield 'GitHub' => [
            SourceType::GitHub,
            [
                'icon' => 'github',
                'color' => '{"red":0.357,"green":0.129,"blue":0.714,"alpha":1}',
                'text' => '#6D28D9',
                'action' => 'Voir le dépôt',
                'label' => 'Dépôt GitHub',
            ],
        ];
        yield 'X' => [
            SourceType::X,
            [
                'icon' => 'x',
                'color' => '{"red":0.012,"green":0.412,"blue":0.631,"alpha":1}',
                'text' => '#0369A1',
                'action' => 'Voir sur X',
                'label' => 'Post X',
            ],
        ];
        yield 'Article' => [
            SourceType::Article,
            [
                'icon' => 'article',
                'color' => '{"red":0.059,"green":0.463,"blue":0.431,"alpha":1}',
                'text' => '#0F766E',
                'action' => 'Lire l\'article',
                'label' => 'Article',
            ],
        ];
        yield 'Image' => [
            SourceType::Image,
            [
                'icon' => 'image',
                'color' => '{"red":0.09,"green":0.094,"blue":0.11,"alpha":1}',
                'text' => '#17181C',
                'action' => 'Voir l\'image',
                'label' => 'Image',
            ],
        ];
    }

    /** @param array{icon: string, color: string, text: string, action: string, label: string} $expected */
    #[DataProvider('provideTypes')]
    public function testCardFollowsTheLinkType(SourceType $type, array $expected): void
    {
        $withContext = $this->link($type, 'Pour la recette.', 'Maestro');

        $this->assertIcon($withContext, $expected['icon'], $expected['label']);
        self::assertStringContainsString(
            sprintf(
                '{"text":"%s","color":%s,"onClick":{"openLink":{"url":"https://maestro.dev"}}}',
                $expected['action'],
                $expected['color'],
            ),
            $this->widgets($withContext),
        );
        self::assertStringContainsString(
            sprintf('<b><font color=\"%s\">marie :</font></b> <i>Pour la recette.</i>', $expected['text']),
            $this->widgets($withContext),
        );
        self::assertStringContainsString(
            sprintf('<font color=\"%s\">Posté par marie</font>', $expected['text']),
            $this->widgets($this->link($type, title: 'Maestro')),
        );
    }

    #[TestWith([SourceType::Article])]
    #[TestWith([SourceType::YouTube])]
    public function testFollowedSourceTakesPrecedenceOverTheType(SourceType $type): void
    {
        $link = $this->link($type, title: 'Maestro', source: $this->source('Symfony Blog'));

        $this->assertIcon($link, 'rss', 'Source suivie');
        self::assertStringContainsString(
            '{"text":"Lire l\'article","color":{"red":0.761,"green":0.255,"blue":0.047,"alpha":1},'
            . '"onClick":{"openLink":{"url":"https://maestro.dev"}}}',
            $this->widgets($link),
        );
        self::assertStringContainsString(
            '<font color=\"#C2410C\">Via le flux Symfony Blog</font>',
            $this->widgets($link),
        );
        self::assertStringNotContainsString('Posté par', $this->widgets($link));
        self::assertStringEndsWith('Via le flux Symfony Blog', $this->message($link)->getSubject());
    }

    public function testSecondaryButtonOpensRadarWithoutBorder(): void
    {
        self::assertStringContainsString(
            '{"text":"Voir dans Radar","type":"BORDERLESS",'
            . '"onClick":{"openLink":{"url":"https://radar.example/app_link_show"}}}',
            $this->widgets($this->link()),
        );
    }

    public function testCardNoLongerCarriesABanner(): void
    {
        self::assertStringNotContainsString('"image"', $this->widgets($this->link(description: 'Tests mobiles.')));
    }

    public function testHeadingMatchesTheSpecification(): void
    {
        $link = $this->link(context: 'Pour la recette.', title: 'Maestro');

        self::assertArrayNotHasKey('header', $this->card($link));
        self::assertSame(
            [
                'startIcon' => [
                    'iconUrl' => 'https://radar.example/app_pagechat/icons/article.png',
                    'imageType' => 'SQUARE',
                    'altText' => 'Article',
                ],
                'text' => '<b>Maestro</b>',
                'bottomLabel' => 'Article · maestro.dev · Tester une app mobile, Outils QA',
            ],
            $this->heading($link),
        );

        $message = $this->message($link);
        self::assertSame(
            'Maestro · Article · maestro.dev · Tester une app mobile, Outils QA · Posté par marie',
            $message->getSubject(),
        );
        self::assertSame($message->getSubject(), $message->getOptions()?->toArray()['fallbackText'] ?? null);
    }

    public function testDomainOnlyAppearsForAnArticle(): void
    {
        self::assertSame(
            'Dépôt GitHub · Tester une app mobile, Outils QA',
            $this->heading($this->link(SourceType::GitHub))['bottomLabel'],
        );
    }

    public function testTitleFallsBackLikeTheApp(): void
    {
        self::assertSame(
            '<b>@symfony sur X</b>',
            $this->heading($this->linkAt('https://x.com/symfony/status/1', SourceType::X))['text'],
        );
        self::assertSame('<b>maestro.dev</b>', $this->heading($this->link())['text']);
    }

    private function assertIcon(Link $link, string $icon, string $altText): void
    {
        $startIcon = $this->heading($link)['startIcon'];
        self::assertSame(sprintf('https://radar.example/app_pagechat/icons/%s.png', $icon), $startIcon['iconUrl']);
        self::assertSame($altText, $startIcon['altText']);
        self::assertFileExists(sprintf('%s/../../public/chat/icons/%s.png', __DIR__, $icon));
    }
}
