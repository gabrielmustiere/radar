<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Category;
use App\Exception\CategoryNameTakenException;
use App\Repository\CategoryRepository;
use App\Service\CategoryManager;
use App\Service\CategoryResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CategoryManagerTest extends KernelTestCase
{
    private CategoryManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $manager = self::getContainer()->get(CategoryManager::class);
        self::assertInstanceOf(CategoryManager::class, $manager);
        $this->manager = $manager;
    }

    public function testRenamedCategoryGetsAnInitialCapitalAndIsSaved(): void
    {
        $category = $this->category('Avant ' . uniqid());
        $marker = uniqid();

        $this->manager->rename($category, '  éco-conception ' . $marker . ' ');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();
        $repository = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $repository);
        self::assertSame('Éco-conception ' . $marker, $repository->find((int) $category->getId())?->getName());
    }

    public function testNameOfAnotherCategoryIsRefusedWhateverTheCase(): void
    {
        $taken = $this->category('Prise ' . uniqid());
        $category = $this->category('Libre ' . uniqid());
        $name = $category->getName();

        try {
            $this->manager->rename($category, mb_strtoupper($taken->getName()));
            self::fail('Le nom d\'une autre catégorie aurait dû être refusé.');
        } catch (CategoryNameTakenException $exception) {
            self::assertSame('Une catégorie porte déjà ce nom.', $exception->getMessage());
        }

        self::assertSame($name, $category->getName());
    }

    public function testCategoryCanChangeTheCaseOfItsOwnName(): void
    {
        $marker = uniqid();
        $category = $this->category('Veille ia ' . $marker);

        $this->manager->rename($category, 'Veille IA ' . $marker);

        self::assertSame('Veille IA ' . $marker, $category->getName());
    }

    private function category(string $name): Category
    {
        $resolver = self::getContainer()->get(CategoryResolver::class);
        self::assertInstanceOf(CategoryResolver::class, $resolver);

        return $resolver->resolve($name, null);
    }
}
