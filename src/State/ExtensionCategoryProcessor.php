<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ExtensionCategory;
use App\Entity\User;
use App\Service\CategoryResolver;
use Symfony\Bundle\SecurityBundle\Security;

/** @implements ProcessorInterface<ExtensionCategory, ExtensionCategory> */
final readonly class ExtensionCategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private CategoryResolver $categoryResolver,
        private Security $security,
    ) {}

    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ExtensionCategory {
        $owner = $this->security->getUser();
        $category = $this->categoryResolver->resolve($data->name, $owner instanceof User ? $owner : null);

        return new ExtensionCategory($category->getId(), $category->getName());
    }
}
