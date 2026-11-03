<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Enum\Type\Team;
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

        $category = $this->resolver->resolve('  ' . $typed . $marker . ' ', null);

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

        $id = $this->resolver->resolve($name, null)->getId();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();
        $repository = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $repository);
        self::assertSame($name, $repository->find((int) $id)?->getName());
    }

    public function testExistingCategoryIsReusedWhateverTheCaseAndKeepsItsName(): void
    {
        $existing = $this->resolver->resolve('Réutilisée ' . uniqid(), null);
        $lowercase = mb_strtolower($existing->getName());

        $reused = $this->resolver->resolve('  ' . mb_strtoupper($existing->getName()) . ' ', null);

        self::assertSame($existing->getId(), $reused->getId());
        self::assertSame($existing->getName(), $reused->getName());
        self::assertNotSame($lowercase, $reused->getName());
    }

    public function testNewCategoryTakesTheTeamOfItsCreator(): void
    {
        $creator = new User()->setTeam(Team::Product);

        $category = $this->resolver->resolve('Équipe du créateur ' . uniqid(), $creator);

        self::assertSame(Team::Product, $category->getTeam());
    }

    public function testNewCategoryHasNoTeamWhenItsCreatorHasNone(): void
    {
        self::assertNull($this->resolver->resolve('Sans équipe ' . uniqid(), new User())->getTeam());
        self::assertNull($this->resolver->resolve('Sans créateur ' . uniqid(), null)->getTeam());
    }

    public function testExistingCategoryKeepsItsTeam(): void
    {
        $existing = $this->resolver->resolve('Équipe gardée ' . uniqid(), new User()->setTeam(Team::Qa));

        $reused = $this->resolver->resolve($existing->getName(), new User()->setTeam(Team::Management));

        self::assertSame($existing->getId(), $reused->getId());
        self::assertSame(Team::Qa, $reused->getTeam());
    }
}
