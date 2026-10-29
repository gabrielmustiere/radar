<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Repository\LinkRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class CategoryNav
{
    public ?int $currentId = null;

    public function __construct(
        private readonly LinkRepository $linkRepository,
    ) {}

    /** @return list<array{id: int, name: string, linkCount: int}> */
    public function getCategories(): array
    {
        return $this->linkRepository->countByCategory();
    }
}
