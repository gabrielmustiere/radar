<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ExtensionCategorySelection;
use App\ApiResource\ExtensionDuplicate;
use App\Repository\CategoryRepository;
use App\Repository\LinkRepository;
use App\Service\LinkManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Complète le lien existant, sans annonce (R5) ; le plafond dépassé entre-temps donne un 409.
 *
 * @implements ProcessorInterface<ExtensionCategorySelection, ExtensionDuplicate>
 */
final readonly class ExtensionDuplicateProcessor implements ProcessorInterface
{
    public function __construct(
        private LinkManager $linkManager,
        private LinkRepository $linkRepository,
        private CategoryRepository $categoryRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ExtensionDuplicate {
        $link = $this->linkRepository->find($uriVariables['id'] ?? null);
        if (null === $link) {
            throw new NotFoundHttpException('Ce lien n\'existe plus.');
        }

        $this->linkManager->complete($link, $this->categoryRepository->findByIdsOrderedByName($data->categories));

        return ExtensionDuplicate::fromLink($link, $this->urlGenerator);
    }
}
