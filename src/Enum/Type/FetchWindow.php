<?php

declare(strict_types=1);

namespace App\Enum\Type;

/** Antériorité d'une relève manuelle : les articles parus depuis lors sont repris, même déjà vus. */
enum FetchWindow: string
{
    case OneHour = '1h';
    case TwelveHours = '12h';
    case ThirtySixHours = '36h';
    case SeventyTwoHours = '72h';

    public function label(): string
    {
        return match ($this) {
            self::OneHour => '1 h',
            self::TwelveHours => '12 h',
            self::ThirtySixHours => '36 h',
            self::SeventyTwoHours => '72 h',
        };
    }

    public function since(\DateTimeImmutable $now): \DateTimeImmutable
    {
        return $now->modify(sprintf('-%d hours', (int) $this->value));
    }
}
