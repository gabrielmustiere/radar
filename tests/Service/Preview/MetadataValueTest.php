<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Service\Preview\MetadataValue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MetadataValueTest extends TestCase
{
    public function testReadsIsoDateInApplicationTimezone(): void
    {
        $date = MetadataValue::date('2024-03-05T01:00:00+09:00');

        self::assertNotNull($date);
        self::assertSame('2024-03-04 17:00', $date->format('Y-m-d H:i'));
        self::assertSame(date_default_timezone_get(), $date->getTimezone()->getName());
    }

    public function testReadsDateWithoutTime(): void
    {
        self::assertSame('2025-03-12', MetadataValue::date('2025-03-12')?->format('Y-m-d'));
    }

    #[DataProvider('notIsoDates')]
    public function testRefusesWhatIsNotAnIsoDate(mixed $value): void
    {
        self::assertNull(MetadataValue::date($value));
    }

    /** @return \Generator<string, array{mixed}> */
    public static function notIsoDates(): \Generator
    {
        yield 'année seule' => ['2024'];
        yield 'mot relatif' => ['now'];
        yield 'date relative' => ['tomorrow'];
        yield 'format libre' => ['March 5, 2024'];
        yield 'date impossible' => ['2024-13-45'];
        yield 'nombre' => [20_240_305];
    }
}
