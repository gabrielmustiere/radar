<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class CategoryResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryRepository $categoryRepository,
        private ClockInterface $clock,
    ) {}

    /** Catégorie existante de même nom (casse ignorée), sinon une nouvelle catégorie persistée au prochain flush. */
    public function resolve(string $name): Category
    {
        $key = mb_strtolower($name);
        foreach ($this->categoryRepository->findAllOrderedByName() as $category) {
            if (mb_strtolower($category->getName()) === $key) {
                return $category;
            }
        }

        $category = new Category($name, $this->clock->now());
        $this->entityManager->persist($category);

        return $category;
    }
}
