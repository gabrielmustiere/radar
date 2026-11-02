<?php

declare(strict_types=1);

namespace App\Exception;

final class CategoryNameTakenException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Une catégorie porte déjà ce nom.');
    }
}
