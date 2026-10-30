<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Enum\Type\PreviewStatus;
use App\Message\RefreshLinkPreview;
use App\MessageHandler\RefreshLinkPreviewHandler;
use App\Repository\LinkRepository;
use App\Tests\Service\Preview\PreviewTestKit;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class RefreshLinkPreviewHandlerTest extends TestCase
{
    public function testRefreshesPreviewWithoutAnnouncingNeverAnnouncedLink(): void
    {
        $link = PreviewTestKit::link('https://example.com/post');
        $repository = $this->createStub(LinkRepository::class);
        $repository->method('find')->willReturn($link);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');
        $html = new MockResponse('<title>Titre</title>', ['response_headers' => ['content-type' => 'text/html']]);

        new RefreshLinkPreviewHandler($repository, PreviewTestKit::enricher(new MockHttpClient($html)), $entityManager)(
            new RefreshLinkPreview(1),
        );

        self::assertSame(PreviewStatus::Ready, $link->getPreviewStatus());
        self::assertNull($link->getAnnouncedAt());
    }
}
