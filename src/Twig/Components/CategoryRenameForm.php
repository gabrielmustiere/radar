<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Category;
use App\Exception\CategoryNameTakenException;
use App\Security\AdminVoter;
use App\Service\CategoryManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Renommage d'une catégorie depuis la modale de sa page, réservé aux admins.
 */
#[AsLiveComponent]
#[IsGranted(AdminVoter::ADMIN)]
final class CategoryRenameForm extends AbstractController
{
    use DefaultActionTrait;

    /** Nulle à l'hydratation si la catégorie a disparu entre-temps. */
    #[LiveProp]
    public ?Category $category = null;

    #[LiveProp(writable: true)]
    #[Assert\NotBlank(message: 'Donnez un nom à la catégorie.')]
    #[Assert\Length(
        min: 2,
        max: 60,
        minMessage: 'Un nom de catégorie compte au moins 2 caractères.',
        maxMessage: 'Un nom de catégorie compte au plus 60 caractères.',
    )]
    public string $name = '';

    public ?string $error = null;

    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->name = $category->getName();
    }

    #[LiveAction]
    public function save(CategoryManager $categoryManager, ValidatorInterface $validator): ?Response
    {
        $category = $this->category ?? throw $this->createNotFoundException();
        $this->name = trim($this->name);
        $violations = $validator->validateProperty($this, 'name');
        if (count($violations) > 0) {
            $this->error = (string) $violations->get(0)->getMessage();

            return null;
        }

        try {
            $categoryManager->rename($category, $this->name);
        } catch (CategoryNameTakenException $exception) {
            $this->error = $exception->getMessage();

            return null;
        }

        $this->addFlash('success', 'Catégorie renommée.');

        return $this->redirectToRoute('app_category_show', ['id' => $category->getId()]);
    }
}
