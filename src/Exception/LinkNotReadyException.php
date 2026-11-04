<?php

declare(strict_types=1);

namespace App\Exception;

final class LinkNotReadyException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('L\'aperçu de ce lien est encore en construction : son annonce ne peut pas être relancée.');
    }
}
