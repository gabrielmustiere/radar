<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Entity\Link;
use App\Form\Model\LinkSubmission;
use App\State\ExtensionDuplicateProcessor;
use App\State\ExtensionDuplicateProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Lien déjà dans Radar sous l'adresse saisie (`?url=`), et le complément de ses catégories. */
#[ApiResource(shortName: 'Duplicate', operations: [
    new Get(uriTemplate: '/extension/duplicate', uriVariables: [], provider: ExtensionDuplicateProvider::class),
    new Post(
        uriTemplate: '/extension/duplicate/{id}/categories',
        uriVariables: ['id'],
        status: 200,
        input: ExtensionCategorySelection::class,
        read: false,
        processor: ExtensionDuplicateProcessor::class,
    ),
])]
final class ExtensionDuplicate
{
    /** @param list<array{id: int, name: string}> $categories tableaux plutôt que ressources, qu'API Platform réduirait à leur IRI */
    public function __construct(
        #[ApiProperty(identifier: true)]
        public int $id,
        public ?string $title,
        public array $categories,
        public int $freeSlots,
        public string $cardUrl,
    ) {}

    public static function fromLink(Link $link, UrlGeneratorInterface $urlGenerator): self
    {
        $categories = [];
        foreach ($link->getCategories() as $category) {
            $categories[] = ['id' => (int) $category->getId(), 'name' => $category->getName()];
        }

        return new self(
            (int) $link->getId(),
            $link->getTitle(),
            $categories,
            max(0, LinkSubmission::MAX_CATEGORIES - count($categories)),
            $urlGenerator->generate('app_link_show', ['id' => $link->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        );
    }
}
