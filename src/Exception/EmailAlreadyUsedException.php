<?php

declare(strict_types=1);

namespace App\Exception;

final class EmailAlreadyUsedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Cet email a déjà un compte.');
    }
}
