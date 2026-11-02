<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\ExtensionLinkProcessor;

/** Les règles d'ajout sont celles du formulaire : le Processor valide une `LinkSubmission`. */
#[ApiResource(shortName: 'Link', operations: [
    new Post(uriTemplate: '/extension/links', validate: false, processor: ExtensionLinkProcessor::class),
])]
final class ExtensionLink
{
    /** @param list<int> $categories */
    public function __construct(
        #[ApiProperty(identifier: true, writable: false)]
        public ?int $id = null,
        public ?string $url = null,
        public ?string $context = null,
        public array $categories = [],
        #[ApiProperty(writable: false)]
        public ?string $cardUrl = null,
    ) {}
}
