<?php

declare(strict_types=1);

namespace App\Enum\Type;

enum Team: string
{
    case Development = 'developpement';
    case Product = 'produit';
    case Qa = 'qa';
    case Management = 'management';

    public function label(): string
    {
        return match ($this) {
            self::Development => 'Développement',
            self::Product => 'Produit',
            self::Qa => 'QA',
            self::Management => 'Management',
        };
    }
}
