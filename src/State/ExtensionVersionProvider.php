<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ExtensionVersion;
use App\Service\ExtensionPackage;

/** @implements ProviderInterface<ExtensionVersion> */
final readonly class ExtensionVersionProvider implements ProviderInterface
{
    public function __construct(
        private ExtensionPackage $extensionPackage,
    ) {}

    #[\Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExtensionVersion
    {
        return new ExtensionVersion($this->extensionPackage->version());
    }
}
