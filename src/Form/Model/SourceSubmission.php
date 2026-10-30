<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Category;
use Symfony\Component\Validator\Constraints as Assert;

class SourceSubmission
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 2048)]
    #[Assert\Url(protocols: ['http', 'https'], requireTld: true)]
    public ?string $feedUrl = null;

    /** @var list<Category> */
    #[Assert\Count(
        min: 1,
        max: LinkSubmission::MAX_CATEGORIES,
        minMessage: 'Choisissez au moins une catégorie.',
        maxMessage: 'Une source porte au plus deux catégories.',
    )]
    public array $categories = [];
}
