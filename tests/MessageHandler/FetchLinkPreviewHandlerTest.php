<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\Link;
use App\Enum\Type\PreviewStatus;
use App\Enum\Type\SourceType;
use App\Message\FetchLinkPreview;
use App\MessageHandler\FetchLinkPreviewHandler;
use App\Repository\LinkRepository;
use App\Service\LinkAnnouncer;
use App\Tests\Service\Preview\PreviewFixture;
use App\Tests\Service\Preview\PreviewTestKit;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FetchLinkPreviewHandlerTest extends TestCase
{
    public function testEnrichesThenAnnouncesWithRealTitle(): void
    {
        $link = PreviewTestKit::link('https://example.com/post');
        $sent = [];

        $this->handler($link, new MockHttpClient($this->html()), $sent)(new FetchLinkPreview(1));

        self::assertSame(PreviewStatus::Ready, $link->getPreviewStatus());
        self::assertNotNull($link->getAnnouncedAt());
        self::assertStringStartsWith('*Titre*', $sent[0] ?? '');
    }

    public function testResolvesXPostThroughOEmbed(): void
    {
        $link = PreviewTestKit::link('https://x.com/symfony/status/2115798561954648231', SourceType::X);
        $client = new MockHttpClient(new MockResponse(PreviewFixture::content('x-oembed.json')));
        $sent = [];

        $this->handler($link, $client, $sent)(new FetchLinkPreview(1));

        self::assertSame(PreviewStatus::Ready, $link->getPreviewStatus());
        self::assertSame('Symfony', $link->getAuthor());
        self::assertSame(1, $client->getRequestsCount());
    }

    public function testRefreshNeverAnnouncesTwice(): void
    {
        $link = PreviewTestKit::link('https://example.com/post');
        $sent = [];

        $this->handler($link, new MockHttpClient($this->html()), $sent)(new FetchLinkPreview(1));
        $this->handler($link, new MockHttpClient($this->html()), $sent)(new FetchLinkPreview(1));

        self::assertCount(1, $sent);
    }

    public function testIgnoresDeletedLink(): void
    {
        $repository = $this->createStub(LinkRepository::class);
        $repository->method('find')->willReturn(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');
        $client = new MockHttpClient();
        $sent = [];

        new FetchLinkPreviewHandler(
            $repository,
            PreviewTestKit::enricher($client),
            $entityManager,
            $this->announcer($sent),
        )(new FetchLinkPreview(1));

        self::assertSame(0, $client->getRequestsCount());
    }

    /** @param list<string> $sent */
    private function handler(Link $link, MockHttpClient $client, array &$sent): FetchLinkPreviewHandler
    {
        $repository = $this->createStub(LinkRepository::class);
        $repository->method('find')->willReturn($link);

        return new FetchLinkPreviewHandler(
            $repository,
            PreviewTestKit::enricher($client),
            $this->createStub(EntityManagerInterface::class),
            $this->announcer($sent),
        );
    }

    /** @param list<string> $sent texte des annonces envoyées */
    private function announcer(array &$sent): LinkAnnouncer
    {
        $chatter = $this->createStub(ChatterInterface::class);
        $chatter
            ->method('send')
            ->willReturnCallback(static function (ChatMessage $message) use (&$sent): null {
                $sent[] = $message->getSubject();

                return null;
            });

        return new LinkAnnouncer($chatter, $this->createStub(UrlGeneratorInterface::class), new MockClock());
    }

    private function html(): MockResponse
    {
        return new MockResponse('<title>Titre</title>', ['response_headers' => ['content-type' => 'text/html']]);
    }
}
