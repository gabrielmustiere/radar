<?php

declare(strict_types=1);

namespace App\Enum\Type;

enum SourceType: string
{
    case GitHub = 'github';
    case YouTube = 'youtube';
    case X = 'x';
    case Image = 'image';
    case Article = 'article';

    public function label(): string
    {
        return match ($this) {
            self::GitHub => 'Dépôt GitHub',
            self::YouTube => 'Vidéo YouTube',
            self::X => 'Post X',
            self::Image => 'Image',
            self::Article => 'Article',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::GitHub => 'tabler:brand-github',
            self::YouTube => 'tabler:brand-youtube',
            self::X => 'tabler:brand-x',
            self::Image => 'tabler:photo',
            self::Article => 'tabler:news',
        };
    }
}
