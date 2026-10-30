<?php

declare(strict_types=1);

namespace App\Exception;

final class AlreadyAdminException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Cette personne est déjà admin.');
    }
}
