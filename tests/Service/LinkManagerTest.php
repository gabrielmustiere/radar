<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Form\Model\LinkSubmission;
use App\Message\FetchLinkPreview;
use App\Repository\CategoryRepository;
use App\Service\LinkManager;
use App\Service\Preview\SourceTypeResolver;
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

    private function manager(?MessageBusInterface $bus = null): LinkManager
    {
        $repository = $this->createStub(CategoryRepository::class);
        $repository->method('findAllOrderedByName')->willReturn([$this->existing]);

        if (null === $bus) {
            $bus = $this->createStub(MessageBusInterface::class);
            $bus->method('dispatch')->willReturnCallback(
                static fn(object $message): Envelope => new Envelope($message),
            );
        }

        return new LinkManager(
            $this->createStub(EntityManagerInterface::class),
            $repository,
            new MockClock('2026-10-29 10:00:00', 'Europe/Paris'),
            new SourceTypeResolver(),
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
