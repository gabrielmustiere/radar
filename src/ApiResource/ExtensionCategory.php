<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\State\ExtensionCategoryProcessor;
use App\State\ExtensionCategoryProvider;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(shortName: 'Category', operations: [
    new GetCollection(
        uriTemplate: '/extension/categories',
        paginationEnabled: false,
        provider: ExtensionCategoryProvider::class,
    ),
    new Post(uriTemplate: '/extension/categories', processor: ExtensionCategoryProcessor::class),
])]
final class ExtensionCategory
{
    public function __construct(
        #[ApiProperty(identifier: true, writable: false)]
        public ?int $id = null,
        #[Assert\Length(
            min: 2,
            max: 60,
            normalizer: 'trim',
            minMessage: 'Un nom de catégorie compte au moins 2 caractères.',
            maxMessage: 'Un nom de catégorie compte au plus 60 caractères.',
        )]
        public string $name = '',
    ) {}
}
