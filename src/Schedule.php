<?php

declare(strict_types=1);

namespace App;

use App\Message\FetchSources;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Consommé par le worker `messenger:consume scheduler_default`. L'état est gardé en cache : une échéance manquée
 * pendant un redémarrage est rattrapée une fois, sans rejouer toutes les heures perdues.
 */
#[AsSchedule]
final readonly class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    #[\Override]
    public function getSchedule(): SymfonySchedule
    {
        return new SymfonySchedule()
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            ->add(RecurringMessage::every('1 hour', new FetchSources()));
    }
}
