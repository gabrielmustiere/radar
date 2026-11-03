<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Enum\Type\Team;
use Symfony\Component\Validator\Constraints as Assert;

class UserEdition
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    public ?Team $team = null;

    public bool $admin = false;
}
