<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Message\FetchSource;
use App\Message\FetchSources;
use App\MessageHandler\FetchSourcesHandler;
use App\Repository\SourceRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class FetchSourcesHandlerTest extends TestCase
{
    public function testDispatchesOneFetchPerActiveSource(): void
    {
        $repository = $this->createStub(SourceRepository::class);
        $repository->method('findActiveIds')->willReturn([3, 7]);
        $dispatched = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $message) use (&$dispatched): Envelope {
                $dispatched[] = $message;

                return new Envelope($message);
            });

        new FetchSourcesHandler($repository, $bus)(new FetchSources());

        self::assertEquals([new FetchSource(3), new FetchSource(7)], $dispatched);
    }
}
