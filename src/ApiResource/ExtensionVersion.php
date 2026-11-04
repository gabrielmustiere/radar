<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\ExtensionVersionProvider;

#[ApiResource(shortName: 'Version', operations: [
    new Get(uriTemplate: '/extension/version', uriVariables: [], provider: ExtensionVersionProvider::class),
])]
final class ExtensionVersion
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public string $version,
    ) {}
}
