<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Category;
use App\Entity\Link;
use App\Exception\CategoryLimitException;
use App\Form\HistoricalLinkType;
use App\Form\LinkType;
use App\Form\Model\HistoricalLinkSubmission;
use App\Form\Model\LinkSubmission;
use App\Repository\CategoryRepository;
use App\Service\LinkManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Formulaire d'ajout (ou de reprise) d'un lien, validé au fil de la saisie.
 * La soumission reste un POST classique vers `LinkController`, pris en charge par Turbo.
 */
#[AsLiveComponent]
final class LinkForm extends AbstractController
{
    use DefaultActionTrait;
    use CategoryPickerTrait;

    /** Reprise d'un lien de l'historique Chat plutôt qu'ajout. */
    #[LiveProp]
    public bool $historical = false;

    public ?string $duplicateNotice = null;

    public function __construct(
        private readonly LinkManager $linkManager,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    #[\Override]
    protected function instantiateForm(): FormInterface
    {
        return $this->historical
            ? $this->createForm(HistoricalLinkType::class, new HistoricalLinkSubmission())
            : $this->createForm(LinkType::class, new LinkSubmission());
    }

    /** Signalé dès que l'adresse est saisie, sans attendre la soumission. */
    public function getDuplicate(): ?Link
    {
        /** @var array{url?: string} $values saisie brute, telle que postée */
        $values = $this->formValues;
        $url = trim($values['url'] ?? '');

        return '' === $url ? null : $this->linkManager->findDuplicate($url);
    }

    /**
     * Catégories cochées que le lien existant n'a pas encore : le membre choisit lesquelles ajouter en (dé)cochant
     * les pastilles du formulaire.
     *
     * @return list<Category>
     */
    public function getDuplicateCandidates(Link $duplicate): array
    {
        return array_values(array_filter(
            $this->categoryRepository->findByIdsOrderedByName($this->checkedCategoryIds()),
            static fn(Category $category): bool => !$duplicate->getCategories()->contains($category),
        ));
    }

    public function getDuplicateFreeSlots(Link $duplicate): int
    {
        return max(0, LinkSubmission::MAX_CATEGORIES - $duplicate->getCategories()->count());
    }

    /**
     * Ajoute au lien existant les catégories cochées qu'il n'a pas, puis ouvre sa carte. Si quelqu'un l'a complété
     * entre-temps, l'alerte est simplement re-rendue à jour.
     */
    #[LiveAction]
    public function completeDuplicate(): ?RedirectResponse
    {
        $duplicate = $this->getDuplicate();
        if (null === $duplicate) {
            throw new \LogicException('Aucun lien existant à compléter.');
        }

        try {
            $this->linkManager->complete($duplicate, $this->getDuplicateCandidates($duplicate));
        } catch (CategoryLimitException) {
            $this->duplicateNotice = 'Ce lien vient d\'être complété par quelqu\'un d\'autre : vérifiez ses catégories.';

            return null;
        }

        $this->addFlash('success', 'Catégories ajoutées.');

        return $this->redirectToRoute('app_link_show', ['id' => $duplicate->getId()]);
    }
}
