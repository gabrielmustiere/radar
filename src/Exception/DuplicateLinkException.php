<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\Link;

final class DuplicateLinkException extends \DomainException
{
    public function __construct(
        public readonly Link $existing,
    ) {
        parent::__construct('Ce lien est déjà dans Radar.');
    }
}
