<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\FetchSource;
use App\Message\FetchSources;
use App\Repository\SourceRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/** Une relève par source sur le transport async : un flux lent ou en erreur ne retarde aucun autre. */
#[AsMessageHandler]
final readonly class FetchSourcesHandler
{
    public function __construct(
        private SourceRepository $sourceRepository,
        private MessageBusInterface $messageBus,
    ) {}

    public function __invoke(FetchSources $_message): void
    {
        foreach ($this->sourceRepository->findActiveIds() as $sourceId) {
            $this->messageBus->dispatch(new FetchSource($sourceId));
        }
    }
}
