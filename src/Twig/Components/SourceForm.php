<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Form\Model\SourceSubmission;
use App\Form\SourceType;
use App\Security\AdminVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Formulaire d'enregistrement d'une source. La soumission reste un POST classique vers `SourceController`.
 * Réservé aux admins : ses actions passent par la route Live générique, hors de `SourceController`.
 */
#[AsLiveComponent]
#[IsGranted(AdminVoter::ADMIN)]
final class SourceForm extends AbstractController
{
    use DefaultActionTrait;
    use CategoryPickerTrait;

    #[\Override]
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(SourceType::class, new SourceSubmission());
    }
}
