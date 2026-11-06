<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Link;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Exception\LinkNotReadyException;
use App\Repository\LinkRepository;
use App\Service\LinkAnnouncementCard;
use App\Service\LinkAnnouncer;
use App\Service\LinkManager;
use App\Service\LinkUrlAnalyzer;
use App\Service\Preview\LinkBannerStore;
use App\Service\Preview\LinkPreview;
use App\Twig\LinkExtension;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LinkManagerReannounceTest extends TestCase
{
    public function testRefusesToReannounceWhilePreviewIsPending(): void
    {
        $chatter = $this->createMock(ChatterInterface::class);
        $chatter->expects($this->never())->method('send');
        $linkRepository = $this->createMock(LinkRepository::class);
        $linkRepository->expects($this->never())->method('save');

        $this->expectException(LinkNotReadyException::class);

        $this->manager($linkRepository, $chatter)->reannounce($this->link());
    }

    public function testReannouncesAndSavesTheNewDate(): void
    {
        $link = $this->link();
        $link->applyPreview(new LinkPreview(title: 'Prêt'), null, null, new \DateTimeImmutable());
        $chatter = $this->createMock(ChatterInterface::class);
        $chatter->expects($this->once())->method('send')->with(self::isInstanceOf(ChatMessage::class));
        $linkRepository = $this->createMock(LinkRepository::class);
        $linkRepository->expects($this->once())->method('save')->with($link);

        $this->manager($linkRepository, $chatter)->reannounce($link);

        self::assertNotNull($link->getAnnouncedAt());
    }

    private function manager(LinkRepository $linkRepository, ChatterInterface $chatter): LinkManager
    {
        $clock = new MockClock();

        return new LinkManager(
            $linkRepository,
            new LinkUrlAnalyzer(),
            $clock,
            $this->createStub(MessageBusInterface::class),
            new LinkAnnouncer(
                $chatter,
                new LinkAnnouncementCard($this->createStub(UrlGeneratorInterface::class), new LinkExtension()),
                $clock,
                new LinkBannerStore(new Filesystem(new InMemoryFilesystemAdapter()), new NullLogger(), 'secret'),
            ),
        );
    }

    private function link(): Link
    {
        return new Link(
            'https://example.com/article',
            'https://example.com/article',
            [],
            new User()->setEmail('marie@example.com'),
            new \DateTimeImmutable(),
            SourceType::Article,
        );
    }
}
