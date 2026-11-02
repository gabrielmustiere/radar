<?php

declare(strict_types=1);

namespace App\ApiResource;

/** Catégories cochées dans la bulle, à ajouter au lien existant. */
final class ExtensionCategorySelection
{
    /** @var list<int> */
    public array $categories = [];
}
