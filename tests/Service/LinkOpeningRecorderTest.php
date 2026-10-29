<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Link;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Repository\LinkOpeningRepository;
use App\Service\LinkOpeningRecorder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class LinkOpeningRecorderTest extends TestCase
{
    #[DataProvider('ages')]
    public function testRecordsAgeInFullDaysSinceOriginDate(string $postedAt, int $expectedAge): void
    {
        $member = new User()->setEmail('karim@example.com');
        $repository = $this->createMock(LinkOpeningRepository::class);
        $repository->expects($this->once())->method('save');
        $recorder = new LinkOpeningRecorder($repository, new MockClock('2026-10-29 10:00', 'Europe/Paris'));

        $opening = $recorder->record($this->link($postedAt), $member);

        self::assertSame($expectedAge, $opening->getLinkAgeDays());
        self::assertSame($member, $opening->getOpenedBy());
        self::assertSame('2026-10-29 10:00', $opening->getOpenedAt()->format('Y-m-d H:i'));
    }

    /** @return \Generator<string, array{string, int}> */
    public static function ages(): \Generator
    {
        yield 'ajouté le matin même' => ['2026-10-29 08:00', 0];
        yield 'veille au soir, moins de 24 h' => ['2026-10-28 18:00', 0];
        yield 'repris de Chat il y a 45 jours' => ['2026-09-14 00:00', 45];
        yield 'date d\'origine dans le futur' => ['2026-10-30 00:00', 0];
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
