<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Psr\Clock\ClockInterface;

final readonly class CategoryResolver
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private ClockInterface $clock,
    ) {}

    /**
     * Catégorie existante de même nom (casse ignorée), renvoyée telle quelle ; sinon une nouvelle catégorie,
     * enregistrée aussitôt avec une majuscule initiale.
     */
    public function resolve(string $name): Category
    {
        $name = trim($name);
        $key = mb_strtolower($name);
        foreach ($this->categoryRepository->findAllOrderedByName() as $category) {
            if (mb_strtolower($category->getName()) === $key) {
                return $category;
            }
        }

        $category = new Category(
            mb_strtoupper(mb_substr($name, start: 0, length: 1)) . mb_substr($name, start: 1),
            $this->clock->now(),
        );
        $this->categoryRepository->save($category);

        return $category;
    }
}
