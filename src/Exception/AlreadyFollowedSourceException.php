<?php

declare(strict_types=1);

namespace App\Exception;

final class AlreadyFollowedSourceException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Ce flux est déjà suivi.');
    }
}
