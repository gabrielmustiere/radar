<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Link;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Service\LinkAnnouncementCard;
use App\Service\LinkAnnouncer;
use App\Service\Preview\LinkBannerStore;
use App\Service\Preview\LinkPreview;
use App\Tests\Service\Preview\TestImage;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LinkAnnouncerTest extends TestCase
{
    public function testAnnouncesOnceAndMarksTheLink(): void
    {
        $link = $this->link();
        $sent = [];

        $this->announcer($sent)->announce($link);
        $this->announcer($sent)->announce($link);

        self::assertCount(1, $sent);
        self::assertEquals(
            new \DateTimeImmutable('2026-10-29 12:00', new \DateTimeZone('Europe/Paris')),
            $link->getAnnouncedAt(),
        );
    }

    public function testBannerOfTheStoredPreviewReachesTheCard(): void
    {
        $link = $this->link();
        $link->applyPreview(new LinkPreview(), 'previews/apercu.webp', null, new \DateTimeImmutable());
        $storage = new Filesystem(new InMemoryFilesystemAdapter());
        $storage->write('previews/apercu.webp', TestImage::png(600, 314));
        $sent = [];

        $this->announcer($sent, $storage)->announce($link);

        self::assertStringContainsString(
            '"image"',
            (string) json_encode(($sent[0] ?? null)?->getOptions()?->toArray()),
        );
    }

    public function testCardStaysCompactWithoutStoredPreview(): void
    {
        $sent = [];

        $this->announcer($sent)->announce($this->link());

        self::assertCount(1, $sent);
        self::assertStringNotContainsString(
            '"image"',
            (string) json_encode(($sent[0] ?? null)?->getOptions()?->toArray()),
        );
    }

    public function testUnqueuedAnnouncementPropagatesAndLeavesTheLinkUnannounced(): void
    {
        $link = $this->link();
        $failure = new \RuntimeException('Chat indisponible');
        $sent = [];
        $thrown = null;

        try {
            $this->announcer($sent, failure: $failure)->announce($link);
        } catch (\RuntimeException $exception) {
            $thrown = $exception;
        }

        self::assertSame($failure, $thrown);
        self::assertNull($link->getAnnouncedAt());
    }

    public function testNeverAnnouncesSeededLink(): void
    {
        $link = $this->link(seeded: true);
        $sent = [];

        $this->announcer($sent)->announce($link);

        self::assertSame([], $sent);
        self::assertNull($link->getAnnouncedAt());
    }

    public function testReannouncesALinkAlreadyAnnouncedAndUpdatesItsDate(): void
    {
        $link = $this->link();
        $link->markAnnounced(new \DateTimeImmutable('2026-01-01'));
        $sent = [];

        $this->announcer($sent)->reannounce($link);

        self::assertCount(1, $sent);
        self::assertEquals(
            new \DateTimeImmutable('2026-10-29 12:00', new \DateTimeZone('Europe/Paris')),
            $link->getAnnouncedAt(),
        );
    }

    public function testReannouncesASeededLink(): void
    {
        $link = $this->link(seeded: true);
        $sent = [];

        $this->announcer($sent)->reannounce($link);

        self::assertCount(1, $sent);
        self::assertNotNull($link->getAnnouncedAt());
    }

    /** @param list<ChatMessage> $sent */
    private function announcer(array &$sent, ?Filesystem $storage = null, ?\Throwable $failure = null): LinkAnnouncer
    {
        $chatter = $this->createStub(ChatterInterface::class);
        $chatter
            ->method('send')
            ->willReturnCallback(static function (ChatMessage $message) use (&$sent, $failure): null {
                if (null !== $failure) {
                    throw $failure;
                }

                $sent[] = $message;

                return null;
            });
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://radar.example/');

        return new LinkAnnouncer(
            $chatter,
            new LinkAnnouncementCard($urlGenerator),
            new MockClock('2026-10-29 12:00', 'Europe/Paris'),
            new LinkBannerStore(
                $storage ?? new Filesystem(new InMemoryFilesystemAdapter()),
                new NullLogger(),
                'secret',
            ),
        );
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
