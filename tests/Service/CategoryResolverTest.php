<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Repository\CategoryRepository;
use App\Service\CategoryResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CategoryResolverTest extends KernelTestCase
{
    private CategoryResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $resolver = self::getContainer()->get(CategoryResolver::class);
        self::assertInstanceOf(CategoryResolver::class, $resolver);
        $this->resolver = $resolver;
    }

    #[DataProvider('newNames')]
    public function testNewCategoryGetsAnInitialCapitalOnly(string $typed, string $expected): void
    {
        $marker = ' ' . uniqid();

        $category = $this->resolver->resolve('  ' . $typed . $marker . ' ');

        self::assertSame($expected . $marker, $category->getName());
    }

    /** @return \Generator<string, array{string, string}> */
    public static function newNames(): \Generator
    {
        yield 'minuscule' => ['tester une app mobile', 'Tester une app mobile'];
        yield 'accent' => ['éco-conception', 'Éco-conception'];
        yield 'sigle conservé' => ['iOS natif', 'IOS natif'];
        yield 'déjà en capitales' => ['API REST', 'API REST'];
    }

    public function testNewCategoryIsSavedRightAway(): void
    {
        $name = 'Enregistrée ' . uniqid();

        $id = $this->resolver->resolve($name)->getId();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();
        $repository = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $repository);
        self::assertSame($name, $repository->find((int) $id)?->getName());
    }

    public function testExistingCategoryIsReusedWhateverTheCaseAndKeepsItsName(): void
    {
        $existing = $this->resolver->resolve('Réutilisée ' . uniqid());
        $lowercase = mb_strtolower($existing->getName());

        $reused = $this->resolver->resolve('  ' . mb_strtoupper($existing->getName()) . ' ');

        self::assertSame($existing->getId(), $reused->getId());
        self::assertSame($existing->getName(), $reused->getName());
        self::assertNotSame($lowercase, $reused->getName());
    }
}
