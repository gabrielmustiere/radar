<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Category;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class LinkSubmission
{
    public const int MAX_CATEGORIES = 2;

    #[Assert\NotBlank]
    #[Assert\Length(max: 2048)]
    #[Assert\Url(protocols: ['http', 'https'], requireTld: true)]
    public ?string $url = null;

    /** @var list<Category> */
    public array $categories = [];

    #[Assert\Length(min: 2, max: 60)]
    public ?string $newCategory = null;

    #[Assert\Length(max: 500)]
    public ?string $context = null;

    #[Assert\Callback]
    public function validateCategoryCount(ExecutionContextInterface $context): void
    {
        $checkedNames = array_map(static fn(Category $category): string => mb_strtolower(
            $category->getName(),
        ), $this->categories);
        $newName = $this->newCategoryName();
        $addsCategory = null !== $newName && !in_array(mb_strtolower($newName), $checkedNames, strict: true);
        $count = count($this->categories) + ($addsCategory ? 1 : 0);

        if (0 === $count) {
            $context
                ->buildViolation('Choisissez au moins une catégorie, ou créez-en une.')
                ->atPath('categories')
                ->addViolation();
        }

        if ($count > self::MAX_CATEGORIES) {
            $context->buildViolation('Un lien porte au plus deux catégories.')->atPath('categories')->addViolation();
        }
    }

    public function newCategoryName(): ?string
    {
        $name = trim($this->newCategory ?? '');

        return '' === $name ? null : $name;
    }
}
