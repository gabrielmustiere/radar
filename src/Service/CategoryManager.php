<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Exception\CategoryNameTakenException;
use App\Repository\CategoryRepository;

final readonly class CategoryManager
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    /**
     * Renomme avec une majuscule initiale, comme à la création. Un nom déjà porté par une autre catégorie (casse
     * ignorée) est refusé ; changer la seule casse de la catégorie elle-même reste possible.
     *
     * @throws CategoryNameTakenException
     */
    public function rename(Category $category, string $name): void
    {
        $name = mb_ucfirst(trim($name));
        $key = mb_strtolower($name);
        foreach ($this->categoryRepository->findAllOrderedByName() as $other) {
            if ($other->getId() !== $category->getId() && mb_strtolower($other->getName()) === $key) {
                throw new CategoryNameTakenException();
            }
        }

        $category->setName($name);
        $this->categoryRepository->save($category);
    }
}
