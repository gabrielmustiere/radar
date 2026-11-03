<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\User;
use App\Form\Model\LinkSubmission;
use App\Service\CategoryResolver;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;

/**
 * Formulaire Live (lien ou source) avec saisie des catégories : la frappe filtre les pastilles, Entrée crée la
 * catégorie pour toute l'équipe et la coche s'il reste de la place.
 *
 * Le formulaire doit porter un champ `categories` (cases à cocher multiples).
 */
trait CategoryPickerTrait
{
    use ComponentWithFormTrait;

    #[LiveProp(writable: true)]
    #[Assert\Length(
        min: 2,
        max: 60,
        minMessage: 'Un nom de catégorie compte au moins 2 caractères.',
        maxMessage: 'Un nom de catégorie compte au plus 60 caractères.',
    )]
    public string $categoryQuery = '';

    public ?string $categoryError = null;

    public ?string $categoryNotice = null;

    #[LiveAction]
    public function createCategory(
        CategoryResolver $categoryResolver,
        ValidatorInterface $validator,
        #[CurrentUser]
        User $creator,
    ): void {
        $this->categoryQuery = trim($this->categoryQuery);
        if ('' === $this->categoryQuery) {
            return;
        }

        $violations = $validator->validateProperty($this, 'categoryQuery');
        if (count($violations) > 0) {
            $this->categoryError = (string) $violations->get(0)->getMessage();

            return;
        }

        $category = $categoryResolver->resolve($this->categoryQuery, $creator);
        $this->categoryQuery = '';
        $id = (string) $category->getId();
        $checked = $this->checkedCategoryIds();
        if (in_array($id, $checked, strict: true)) {
            return;
        }

        if (count($checked) >= LinkSubmission::MAX_CATEGORIES) {
            $this->categoryNotice = sprintf(
                '« %s » est disponible : désélectionnez une catégorie pour la retenir.',
                $category->getName(),
            );

            return;
        }

        $this->formValues['categories'] = [...$checked, $id];
    }

    public function getCheckedCategoryCount(): int
    {
        return count($this->checkedCategoryIds());
    }

    public function isCategoryVisible(string $name): bool
    {
        $query = trim($this->categoryQuery);

        return '' === $query || false !== mb_stripos($name, $query);
    }

    /** @return list<string> */
    private function checkedCategoryIds(): array
    {
        /** @var array{categories?: list<int|string>} $values saisie brute, telle que postée */
        $values = $this->formValues;

        return array_map(strval(...), $values['categories'] ?? []);
    }
}
