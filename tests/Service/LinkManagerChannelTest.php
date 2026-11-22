<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Source;
use App\Entity\User;
use App\Enum\Type\LinkChannel;
use App\Form\Model\HistoricalLinkSubmission;
use App\Form\Model\LinkSubmission;
use App\Repository\LinkRepository;
use App\Service\LinkAnnouncementCard;
use App\Service\LinkAnnouncer;
use App\Service\LinkManager;
use App\Service\LinkUrlAnalyzer;
use App\Service\Preview\LinkBannerStore;
use App\Twig\LinkExtension;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LinkManagerChannelTest extends TestCase
{
    public function testAddRecordsTheChannelOfTheSubmission(): void
    {
        self::assertSame(LinkChannel::Web, $this->manager()->add($this->submission(), $this->user())->getChannel());

        $shared = $this->submission();
        $shared->channel = LinkChannel::Share;

        self::assertSame(LinkChannel::Share, $this->manager()->add($shared, $this->user())->getChannel());
    }

    public function testSeededLinkHasNoChannel(): void
    {
        $submission = new HistoricalLinkSubmission();
        $submission->url = 'https://example.com/historique';
        $submission->categories = [$this->category()];
        $submission->postedAt = new \DateTimeImmutable('2026-03-15');
        $submission->poster = $this->user();

        self::assertNull($this->manager()->seed($submission)->getChannel());
    }

    public function testPublishedArticleHasNoChannel(): void
    {
        $source = new Source('https://example.com/feed', 'Blog', [$this->category()], new \DateTimeImmutable());

        self::assertNull($this->manager()->publish($source, 'https://example.com/billet', $this->user())->getChannel());
    }

    private function manager(): LinkManager
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(static fn(object $message): Envelope => new Envelope($message));

        return new LinkManager(
            $this->createStub(LinkRepository::class),
            new LinkUrlAnalyzer(),
            new MockClock(),
            $bus,
            new LinkAnnouncer(
                $this->createStub(ChatterInterface::class),
                new LinkAnnouncementCard($this->createStub(UrlGeneratorInterface::class), new LinkExtension()),
                new MockClock(),
                new LinkBannerStore(new Filesystem(new InMemoryFilesystemAdapter()), new NullLogger(), 'secret'),
            ),
        );
    }

    private function submission(): LinkSubmission
    {
        $submission = new LinkSubmission();
        $submission->url = 'https://example.com/article';
        $submission->categories = [$this->category()];

        return $submission;
    }

    private function category(): Category
    {
        return new Category('Tester une app mobile', new \DateTimeImmutable('2026-01-01'));
    }

    private function user(): User
    {
        return new User()->setEmail('marie@example.com');
    }
}
