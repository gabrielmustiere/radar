<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Link;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Exception\CategoryLimitException;
use App\Repository\LinkRepository;
use App\Service\LinkManager;
use App\Service\LinkUrlAnalyzer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\MessageBusInterface;

final class LinkManagerCompleteTest extends TestCase
{
    public function testAddsOnlyMissingCategoriesWithoutAnnouncing(): void
    {
        $mobile = $this->category('Tester une app mobile');
        $orm = $this->category('Choisir un ORM');
        $link = $this->link([$mobile]);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');
        $linkRepository = $this->createMock(LinkRepository::class);
        $linkRepository->expects($this->once())->method('save')->with($link);

        $this->manager($linkRepository, $bus)->complete($link, [$mobile, $orm, $orm]);

        self::assertSame([$mobile, $orm], array_values($link->getCategories()->toArray()));
    }

    public function testRefusesToExceedThreeCategoriesAndChangesNothing(): void
    {
        $link = $this->link([$this->category('Un'), $this->category('Deux')]);
        $linkRepository = $this->createMock(LinkRepository::class);
        $linkRepository->expects($this->never())->method('save');

        try {
            $this->manager($linkRepository)->complete($link, [$this->category('Trois'), $this->category('Quatre')]);
            self::fail('Le plafond de trois catégories aurait dû être refusé.');
        } catch (CategoryLimitException) {
            self::assertCount(2, $link->getCategories());
        }
    }

    private function manager(LinkRepository $linkRepository, ?MessageBusInterface $bus = null): LinkManager
    {
        return new LinkManager(
            $linkRepository,
            new LinkUrlAnalyzer(),
            new MockClock(),
            $bus ?? $this->createStub(MessageBusInterface::class),
        );
    }

    private function category(string $name): Category
    {
        return new Category($name, new \DateTimeImmutable('2026-01-01'));
    }

    /** @param list<Category> $categories */
    private function link(array $categories): Link
    {
        return new Link(
            'https://example.com/article',
            'https://example.com/article',
            $categories,
            new User()->setEmail('marie@example.com'),
            new \DateTimeImmutable(),
            SourceType::Article,
        );
    }
}
