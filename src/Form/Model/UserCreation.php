<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Enum\Type\Team;
use Symfony\Component\Validator\Constraints as Assert;

class UserCreation
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\NotNull(message: 'Choisissez une équipe.')]
    public ?Team $team = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 4096)]
    public ?string $password = null;

    public bool $admin = false;
}
