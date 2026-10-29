<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\Link;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Twig\LinkExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class LinkExtensionTest extends TestCase
{
    #[DataProvider('days')]
    public function testDayLabel(string $date, string $expected): void
    {
        $extension = new LinkExtension(new MockClock('2026-10-29 09:00', 'Europe/Paris'));

        self::assertSame(
            $expected,
            $extension->dayLabel(new \DateTimeImmutable($date, new \DateTimeZone('Europe/Paris'))),
        );
    }

    /** @return \Generator<string, array{string, string}> */
    public static function days(): \Generator
    {
        yield 'aujourd\'hui' => ['2026-10-29 00:05', 'Aujourd\'hui'];
        yield 'hier' => ['2026-10-28 23:59', 'Hier'];
        yield 'même année' => ['2026-10-26 12:00', 'Lundi 26 octobre'];
        yield 'autre année' => ['2025-03-15 12:00', 'Samedi 15 mars 2025'];
    }

    public function testByDayGroupsConsecutiveLinksByParisDay(): void
    {
        $extension = new LinkExtension(new MockClock());
        $late = $this->link('2026-10-28 23:30');
        $sameDay = $this->link('2026-10-28 08:00');
        $previous = $this->link('2026-10-27 22:00');

        $days = $extension->byDay([$late, $sameDay, $previous]);

        self::assertSame(['2026-10-28' => [$late, $sameDay], '2026-10-27' => [$previous]], $days);
    }

    private function link(string $postedAt): Link
    {
        return new Link(
            'https://example.com',
            'https://example.com',
            [],
            new User()->setEmail('marie@example.com'),
            new \DateTimeImmutable($postedAt, new \DateTimeZone('Europe/Paris')),
            SourceType::Article,
        );
    }
}
