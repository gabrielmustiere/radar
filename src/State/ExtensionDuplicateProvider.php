<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ExtensionDuplicate;
use App\Service\LinkManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** @implements ProviderInterface<ExtensionDuplicate> */
final readonly class ExtensionDuplicateProvider implements ProviderInterface
{
    public function __construct(
        private LinkManager $linkManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    #[\Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ExtensionDuplicate
    {
        $request = $context['request'] ?? null;
        $url = $request instanceof Request ? trim($request->query->getString('url')) : '';
        $link = '' === $url ? null : $this->linkManager->findDuplicate($url);
        if (null === $link) {
            return null;
        }

        return ExtensionDuplicate::fromLink($link, $this->urlGenerator);
    }
}
