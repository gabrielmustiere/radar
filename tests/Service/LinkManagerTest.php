<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Link;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Exception\DuplicateLinkException;
use App\Form\Model\HistoricalLinkSubmission;
use App\Form\Model\LinkSubmission;
use App\Message\FetchLinkPreview;
use App\Repository\CategoryRepository;
use App\Repository\LinkRepository;
use App\Service\CategoryResolver;
use App\Service\LinkManager;
use App\Service\LinkUrlAnalyzer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class LinkManagerTest extends TestCase
{
    private Category $existing;

    #[\Override]
    protected function setUp(): void
    {
        $this->existing = new Category('Tester une app mobile', new \DateTimeImmutable('2026-01-01'));
    }

    public function testReusesExistingCategoryIgnoringCaseAndSpaces(): void
    {
        $submission = $this->submission(newCategory: '  tester une APP mobile ');

        $link = $this->manager()->add($submission, $this->user());

        self::assertSame([$this->existing], $link->getCategories()->toArray());
    }

    public function testCreatesMissingCategoryAlongsideCheckedOne(): void
    {
        $submission = $this->submission(newCategory: 'Choisir un ORM');
        $submission->categories = [$this->existing];

        $link = $this->manager()->add($submission, $this->user());

        $names = array_map(
            static fn(Category $category): string => $category->getName(),
            $link->getCategories()->toArray(),
        );
        self::assertSame(['Tester une app mobile', 'Choisir un ORM'], array_values($names));
    }

    public function testSetsPosterDateAndEmptyContextToNull(): void
    {
        $submission = $this->submission(newCategory: 'Choisir un ORM');
        $submission->context = '   ';
        $user = $this->user();

        $link = $this->manager()->add($submission, $user);

        self::assertSame($user, $link->getPostedBy());
        self::assertEquals(
            new \DateTimeImmutable('2026-10-29 10:00:00', new \DateTimeZone('Europe/Paris')),
            $link->getPostedAt(),
        );
        self::assertNull($link->getContext());
        self::assertSame('https://example.com/article', $link->getUrl());
    }

    public function testResolvesSourceTypeAndDispatchesPreviewFetch(): void
    {
        $submission = $this->submission(newCategory: 'Choisir un ORM');
        $submission->url = 'https://github.com/symfony/symfony';
        $bus = $this->createMock(MessageBusInterface::class);
        $bus
            ->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(FetchLinkPreview::class))
            ->willReturnCallback(static fn(object $message): Envelope => new Envelope($message));

        $link = $this->manager($bus)->add($submission, $this->user());

        self::assertSame(SourceType::GitHub, $link->getSourceType());
    }

    public function testRefusesDuplicateWithoutPersistingAnything(): void
    {
        $existing = new Link(
            'https://example.com/article',
            'https://example.com/article',
            [],
            $this->user(),
            new \DateTimeImmutable(),
            SourceType::Article,
        );
        $linkRepository = $this->createMock(LinkRepository::class);
        $linkRepository->method('findOneByNormalizedUrl')->willReturn($existing);
        $linkRepository->expects($this->never())->method('save');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        try {
            $this->manager(entityManager: $entityManager, linkRepository: $linkRepository)->add(
                $this->submission(newCategory: 'Choisir un ORM'),
                $this->user(),
            );
            self::fail('Le doublon aurait dû être refusé.');
        } catch (DuplicateLinkException $exception) {
            self::assertSame($existing, $exception->existing);
        }
    }

    public function testSeedKeepsOriginalPosterAndDateAndIsNeverAnnounced(): void
    {
        $submission = new HistoricalLinkSubmission();
        $submission->url = 'https://example.com/historique';
        $submission->categories = [$this->existing];
        $submission->postedAt = new \DateTimeImmutable('2026-03-15');
        $submission->poster = new User()->setEmail('karim@example.com');

        $link = $this->manager()->seed($submission);

        self::assertSame($submission->poster, $link->getPostedBy());
        self::assertSame($submission->postedAt, $link->getPostedAt());
        self::assertTrue($link->isSeeded());
    }

    private function manager(
        ?MessageBusInterface $bus = null,
        ?EntityManagerInterface $entityManager = null,
        ?LinkRepository $linkRepository = null,
    ): LinkManager {
        $repository = $this->createStub(CategoryRepository::class);
        $repository->method('findAllOrderedByName')->willReturn([$this->existing]);

        if (null === $bus) {
            $bus = $this->createStub(MessageBusInterface::class);
            $bus->method('dispatch')->willReturnCallback(
                static fn(object $message): Envelope => new Envelope($message),
            );
        }

        $clock = new MockClock('2026-10-29 10:00:00', 'Europe/Paris');

        return new LinkManager(
            $linkRepository ?? $this->createStub(LinkRepository::class),
            new LinkUrlAnalyzer(),
            new CategoryResolver(
                $entityManager ?? $this->createStub(EntityManagerInterface::class),
                $repository,
                $clock,
            ),
            $clock,
            $bus,
        );
    }

    private function submission(?string $newCategory = null): LinkSubmission
    {
        $submission = new LinkSubmission();
        $submission->url = 'https://example.com/article';
        $submission->newCategory = $newCategory;

        return $submission;
    }

    private function user(): User
    {
        return new User()->setEmail('marie@example.com');
    }
}
