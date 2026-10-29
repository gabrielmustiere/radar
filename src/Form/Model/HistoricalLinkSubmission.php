<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

final class HistoricalLinkSubmission extends LinkSubmission
{
    #[Assert\NotNull(message: 'Indiquez la date à laquelle le lien a été posté dans Chat.')]
    #[Assert\LessThanOrEqual('today', message: 'La date d\'origine ne peut pas être dans le futur.')]
    public ?\DateTimeImmutable $postedAt = null;

    #[Assert\NotNull(message: 'Indiquez qui avait posté le lien.')]
    public ?User $poster = null;
}
