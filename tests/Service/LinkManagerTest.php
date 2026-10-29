<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\User;
use App\Form\Model\LinkSubmission;
use App\Repository\CategoryRepository;
use App\Service\LinkManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

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

    private function manager(): LinkManager
    {
        $repository = $this->createStub(CategoryRepository::class);
        $repository->method('findAllOrderedByName')->willReturn([$this->existing]);

        return new LinkManager(
            $this->createStub(EntityManagerInterface::class),
            $repository,
            new MockClock('2026-10-29 10:00:00', 'Europe/Paris'),
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
