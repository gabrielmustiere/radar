<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\Link;
use App\Entity\User;
use App\Enum\Type\PreviewStatus;
use App\Enum\Type\SourceType;
use App\Message\FetchLinkPreview;
use App\MessageHandler\FetchLinkPreviewHandler;
use App\Repository\LinkRepository;
use App\Service\Preview\LinkPreviewFetcher;
use App\Service\Preview\LinkPreviewParser;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FetchLinkPreviewHandlerTest extends TestCase
{
    public function testAppliesPreviewWhenSourceExposesOne(): void
    {
        $link = $this->link('https://example.com/post', SourceType::Article);
        $html = new MockResponse('<meta property="og:title" content="Titre">', ['response_headers' => [
            'content-type' => 'text/html',
        ]]);

        $this->handler($link, new MockHttpClient($html))(new FetchLinkPreview(1));

        self::assertSame(PreviewStatus::Ready, $link->getPreviewStatus());
        self::assertSame('Titre', $link->getTitle());
    }

    public function testMarksPreviewUnavailableWhenSourceFails(): void
    {
        $link = $this->link('https://example.com/post', SourceType::Article);

        $this->handler($link, new MockHttpClient(new MockResponse('', ['http_code' => 500])))(new FetchLinkPreview(1));

        self::assertSame(PreviewStatus::Unavailable, $link->getPreviewStatus());
    }

    public function testNeverQueriesX(): void
    {
        $link = $this->link('https://x.com/someone/status/1', SourceType::X);
        $client = new MockHttpClient(new MockResponse('<title>X</title>', ['response_headers' => [
            'content-type' => 'text/html',
        ]]));

        $this->handler($link, $client)(new FetchLinkPreview(1));

        self::assertSame(PreviewStatus::Unavailable, $link->getPreviewStatus());
        self::assertSame(0, $client->getRequestsCount());
    }

    public function testIgnoresDeletedLink(): void
    {
        $repository = $this->createStub(LinkRepository::class);
        $repository->method('find')->willReturn(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');
        $client = new MockHttpClient();

        new FetchLinkPreviewHandler($repository, $this->fetcher($client), $entityManager)(new FetchLinkPreview(1));

        self::assertSame(0, $client->getRequestsCount());
    }

    private function handler(Link $link, MockHttpClient $client): FetchLinkPreviewHandler
    {
        $repository = $this->createStub(LinkRepository::class);
        $repository->method('find')->willReturn($link);

        return new FetchLinkPreviewHandler(
            $repository,
            $this->fetcher($client),
            $this->createStub(EntityManagerInterface::class),
        );
    }

    private function fetcher(MockHttpClient $client): LinkPreviewFetcher
    {
        return new LinkPreviewFetcher($client, new LinkPreviewParser(), new NullLogger());
    }

    private function link(string $url, SourceType $sourceType): Link
    {
        return new Link(
            $url,
            $url,
            [],
            new User()->setEmail('marie@example.com'),
            new \DateTimeImmutable(),
            $sourceType,
        );
    }
}
