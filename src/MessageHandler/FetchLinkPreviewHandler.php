<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\Type\SourceType;
use App\Message\FetchLinkPreview;
use App\Repository\LinkRepository;
use App\Service\Preview\LinkPreviewFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FetchLinkPreviewHandler
{
    public function __construct(
        private LinkRepository $linkRepository,
        private LinkPreviewFetcher $fetcher,
        private EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(FetchLinkPreview $message): void
    {
        $link = $this->linkRepository->find($message->linkId);
        if (null === $link) {
            return;
        }

        // X ferme ses aperçus aux accès anonymes : la carte de repli est le cas nominal.
        $preview = SourceType::X === $link->getSourceType() ? null : $this->fetcher->fetch($link->getUrl());
        null === $preview
            ? $link->markPreviewUnavailable()
            : $link->applyPreview($preview->title, $preview->description, $preview->imageUrl, $preview->siteName);

        $this->entityManager->flush();
    }
}
