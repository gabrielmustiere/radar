<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ExtensionCategory;
use App\Entity\Category;
use App\Repository\CategoryRepository;

/** @implements ProviderInterface<ExtensionCategory> */
final readonly class ExtensionCategoryProvider implements ProviderInterface
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    /** @return list<ExtensionCategory> */
    #[\Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return array_map(
            static fn(Category $category): ExtensionCategory => new ExtensionCategory(
                $category->getId(),
                $category->getName(),
            ),
            $this->categoryRepository->findAllOrderedByName(),
        );
    }
}
