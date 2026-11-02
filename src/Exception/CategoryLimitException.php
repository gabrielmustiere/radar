<?php

declare(strict_types=1);

namespace App\Exception;

final class CategoryLimitException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Un lien porte au plus trois catégories.');
    }
}
