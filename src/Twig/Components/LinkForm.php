<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Link;
use App\Form\HistoricalLinkType;
use App\Form\LinkType;
use App\Form\Model\HistoricalLinkSubmission;
use App\Form\Model\LinkSubmission;
use App\Service\LinkManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Formulaire d'ajout (ou de reprise) d'un lien, validé au fil de la saisie.
 * La soumission reste un POST classique vers `LinkController`, pris en charge par Turbo.
 */
#[AsLiveComponent]
final class LinkForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    /** Reprise d'un lien de l'historique Chat plutôt qu'ajout. */
    #[LiveProp]
    public bool $historical = false;

    public function __construct(
        private readonly LinkManager $linkManager,
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
        $url = trim($this->values()['url'] ?? '');

        return '' === $url ? null : $this->linkManager->findDuplicate($url);
    }

    /** Catégories retenues : cochées, plus la nouvelle si elle est saisie. */
    public function getCategoryCount(): int
    {
        $values = $this->values();

        return count($values['categories'] ?? []) + ('' === trim($values['newCategory'] ?? '') ? 0 : 1);
    }

    /** @return array{url?: string, categories?: list<string>, newCategory?: string} saisie brute, telle que postée */
    private function values(): array
    {
        /** @var array{url?: string, categories?: list<string>, newCategory?: string} */
        return $this->formValues;
    }
}
