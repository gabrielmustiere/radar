<?php

declare(strict_types=1);

namespace App\Tests;

use App\Message\FetchSources;
use App\Schedule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Scheduler\Generator\MessageContext;

final class ScheduleTest extends TestCase
{
    public function testSourcesAreFetchedEveryHour(): void
    {
        $messages = new Schedule(new ArrayAdapter())->getSchedule()->getRecurringMessages();

        self::assertCount(1, $messages);
        $message = $messages[0] ?? self::fail('Aucune échéance planifiée.');
        self::assertSame('every 1 hour', (string) $message->getTrigger());
        $context = new MessageContext('default', 'id', $message->getTrigger(), new \DateTimeImmutable());
        self::assertInstanceOf(FetchSources::class, iterator_to_array($message->getMessages($context))[0] ?? null);
    }
}
