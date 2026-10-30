<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\FetchLinkPreview;
use App\Repository\LinkRepository;
use App\Service\LinkAnnouncer;
use App\Service\Preview\LinkEnricher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FetchLinkPreviewHandler
{
    public function __construct(
        private LinkRepository $linkRepository,
        private LinkEnricher $enricher,
        private EntityManagerInterface $entityManager,
        private LinkAnnouncer $announcer,
    ) {}

    public function __invoke(FetchLinkPreview $message): void
    {
        $link = $this->linkRepository->find($message->linkId);
        if (null === $link) {
            return;
        }

        $this->enricher->enrich($link);

        // L'annonce attend l'aperçu pour porter le vrai titre.
        $this->announcer->announce($link);
        $this->entityManager->flush();
    }
}
