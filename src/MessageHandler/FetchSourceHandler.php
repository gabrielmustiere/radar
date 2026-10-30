<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\FetchSource;
use App\Repository\SourceRepository;
use App\Service\SourcePublisher;
use App\Service\SystemUserProvider;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FetchSourceHandler
{
    public function __construct(
        private SourceRepository $sourceRepository,
        private SourcePublisher $publisher,
        private SystemUserProvider $systemUserProvider,
    ) {}

    public function __invoke(FetchSource $message): void
    {
        $source = $this->sourceRepository->find($message->sourceId);
        if (null === $source || !$source->isActive()) {
            return;
        }

        $this->publisher->fetch($source, $this->systemUserProvider->radar());
    }
}
