<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Link;
use App\Entity\Source;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Service\LinkAnnouncer;
use App\Service\Preview\LinkPreview;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LinkAnnouncerTest extends TestCase
{
    public function testAnnouncesTitleCategoriesPosterContextAndLinksOnce(): void
    {
        $link = $this->link(context: 'Pour la recette mobile.');
        $link->applyPreview(new LinkPreview(title: 'Maestro, tests mobiles'), null, null, new \DateTimeImmutable());
        $sent = [];

        $this->announcer($sent)->announce($link);
        $this->announcer($sent)->announce($link);

        self::assertCount(1, $sent);
        self::assertSame(
            "*Maestro, tests mobiles*\nTester une app mobile, Outils QA · posté par marie\n_« Pour la recette mobile. »_\n"
            . '<https://maestro.dev|Ouvrir le lien> · <https://radar.example/liens/42|Voir dans Radar>',
            $sent[0] ?? null,
        );
        self::assertEquals(
            new \DateTimeImmutable('2026-10-29 12:00', new \DateTimeZone('Europe/Paris')),
            $link->getAnnouncedAt(),
        );
    }

    public function testFallsBackOnUrlWithoutTitleOrContext(): void
    {
        $sent = [];

        $this->announcer($sent)->announce($this->link());

        self::assertStringStartsWith(
            "*https://maestro.dev*\nTester une app mobile, Outils QA · posté par marie\n<",
            $sent[0] ?? '',
        );
    }

    public function testNamesTheSourceOfAnAutomaticLink(): void
    {
        $source = new Source('https://symfony.com/feed', 'Symfony Blog', [], new \DateTimeImmutable());
        $link = new Link(
            'https://symfony.com/blog/article',
            'https://symfony.com/blog/article',
            [new Category('Symfony', new \DateTimeImmutable())],
            new User()->setEmail('radar@radar.invalid'),
            new \DateTimeImmutable(),
            SourceType::Article,
            source: $source,
        );
        $sent = [];

        $this->announcer($sent)->announce($link);

        self::assertStringContainsString("\nSymfony · posté par radar via Symfony Blog\n", $sent[0] ?? '');
    }

    public function testNeverAnnouncesSeededLink(): void
    {
        $link = $this->link(seeded: true);
        $sent = [];

        $this->announcer($sent)->announce($link);

        self::assertSame([], $sent);
        self::assertNull($link->getAnnouncedAt());
    }

    /** @param list<string> $sent */
    private function announcer(array &$sent): LinkAnnouncer
    {
        $chatter = $this->createStub(ChatterInterface::class);
        $chatter
            ->method('send')
            ->willReturnCallback(static function (ChatMessage $message) use (&$sent): null {
                $sent[] = $message->getSubject();

                return null;
            });
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://radar.example/liens/42');

        return new LinkAnnouncer($chatter, $urlGenerator, new MockClock('2026-10-29 12:00', 'Europe/Paris'));
    }

    private function link(?string $context = null, bool $seeded = false): Link
    {
        $categories = [
            new Category('Tester une app mobile', new \DateTimeImmutable()),
            new Category('Outils QA', new \DateTimeImmutable()),
        ];
        $poster = new User()->setEmail('marie@example.com');

        return new Link(
            'https://maestro.dev',
            'https://maestro.dev',
            $categories,
            $poster,
            new \DateTimeImmutable(),
            SourceType::Article,
            $context,
            $seeded,
        );
    }
}
