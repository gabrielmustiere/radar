<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ExtensionCategory;
use App\Service\CategoryResolver;

/** @implements ProcessorInterface<ExtensionCategory, ExtensionCategory> */
final readonly class ExtensionCategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private CategoryResolver $categoryResolver,
    ) {}

    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ExtensionCategory {
        $category = $this->categoryResolver->resolve($data->name);

        return new ExtensionCategory($category->getId(), $category->getName());
    }
}
