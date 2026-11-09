<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Link;
use App\Entity\Source;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Service\Preview\LinkPreview;

final class LinkAnnouncementCardSafetyTest extends LinkAnnouncementCardTestCase
{
    private const string HOSTILE = '<users/all><a href="https://piege.example">clic</a>';

    public function testNoUntrustedFragmentKeepsItsMarkup(): void
    {
        $posted = $this->hostileLink(self::HOSTILE, null);
        $followed = $this->hostileLink(null, $this->source(self::HOSTILE));

        foreach ([$posted, $followed] as $link) {
            $message = $this->message($link);

            self::assertStringNotContainsString(
                '<',
                $this->withoutOwnMarkup($this->json($message->getOptions()?->toArray())),
            );
            self::assertStringNotContainsString('<', $message->getSubject());
        }

        self::assertStringContainsString('<i>‹users/all›‹a href=', $this->widgets($posted));
        self::assertStringContainsString('Via le flux ‹users/all›', $this->widgets($followed));
    }

    public function testEscapesAHostileHostInTheTitleAndTheDomain(): void
    {
        $link = $this->linkAt('https://exa<x>mple.com/article', SourceType::Article);
        $message = $this->message($link);

        self::assertStringNotContainsString(
            '<',
            $this->withoutOwnMarkup($this->json($message->getOptions()?->toArray())),
        );
        self::assertStringNotContainsString('<', $message->getSubject());
        self::assertSame('<b>exa‹x›mple.com</b>', $this->heading($link)['text']);
        self::assertSame('Article · exa‹x›mple.com', $this->heading($link)['bottomLabel']);
    }

    public function testEncodesAnUrlThatWouldBreakOutOfItsMarkup(): void
    {
        $link = $this->linkAt('https://example.com/a|b>c<d', SourceType::Article);

        self::assertSame('<b>example.com</b>', $this->heading($link)['text']);
        self::assertStringContainsString('"url":"https://example.com/a%7Cb%3Ec%3Cd"', $this->widgets($link));
    }

    public function testSurvivesAnUrlWithoutHostAndALinkWithoutCategory(): void
    {
        $link = $this->linkAt('mailto:marie', SourceType::Article);

        self::assertSame('Article', $this->heading($link)['bottomLabel']);
    }

    private function withoutOwnMarkup(string $json): string
    {
        return (string) preg_replace(
            '~</?[bi]>|<font color=\\\\"#[0-9A-F]{6}\\\\">|</font>~',
            replacement: '',
            subject: $json,
        );
    }

    private function hostileLink(?string $context, ?Source $source): Link
    {
        $link = new Link(
            'https://example.com/article',
            'https://example.com/article',
            [new Category(self::HOSTILE, new \DateTimeImmutable())],
            new User()->setEmail(self::HOSTILE . '@example.com'),
            new \DateTimeImmutable(),
            SourceType::Article,
            $context,
            source: $source,
        );
        $link->applyPreview(
            new LinkPreview(title: self::HOSTILE, description: self::HOSTILE . ' et davantage'),
            null,
            null,
            new \DateTimeImmutable(),
        );

        return $link;
    }
}
