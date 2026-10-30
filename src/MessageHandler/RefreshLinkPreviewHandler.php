<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\RefreshLinkPreview;
use App\Repository\LinkRepository;
use App\Service\Preview\LinkEnricher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RefreshLinkPreviewHandler
{
    public function __construct(
        private LinkRepository $linkRepository,
        private LinkEnricher $enricher,
        private EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(RefreshLinkPreview $message): void
    {
        $link = $this->linkRepository->find($message->linkId);
        if (null === $link) {
            return;
        }

        $this->enricher->enrich($link);
        $this->entityManager->flush();
    }
}
