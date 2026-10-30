<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Source;
use App\Entity\SourceItem;
use App\Entity\User;
use App\Exception\InvalidFeedException;
use App\Feed\FeedReader;
use App\Repository\SourceItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Relève d'une source : chaque article jamais vu est publié, du plus ancien au plus récent, puis marqué vu.
 * Une relève manuelle peut remonter plus loin (`$since`) : les articles parus depuis lors sont repris même déjà vus,
 * comme ceux présents à l'ajout de la source. Un article déjà dans Radar n'est jamais publié deux fois (R6).
 * Un flux en erreur est noté sur la source sans lever d'exception : la relève suivante réessaiera.
 */
final readonly class SourcePublisher
{
    public function __construct(
        private FeedReader $feedReader,
        private SourceItemRepository $sourceItemRepository,
        private LinkManager $linkManager,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {}

    /** @return ?int le nombre d'articles publiés, `null` quand le flux n'a pas pu être lu (erreur notée sur la source) */
    public function fetch(Source $source, User $poster, ?\DateTimeImmutable $since = null): ?int
    {
        try {
            $feed = $this->feedReader->read($source->getFeedUrl());
        } catch (InvalidFeedException $exception) {
            $source->markFailed($exception->getMessage(), $this->clock->now());
            $this->entityManager->flush();

            return null;
        }

        $seen = $this->sourceItemRepository->findSeenKeys($source, $feed->keys());
        $published = 0;
        foreach (array_reverse($feed->items) as $item) {
            $alreadySeen = in_array($item->key, $seen, strict: true);
            if ($alreadySeen && (null === $since || !$item->isPublishedSince($since))) {
                continue;
            }

            // Persisté avant la publication pour partir dans le même flush que le lien.
            if (!$alreadySeen) {
                $this->entityManager->persist(new SourceItem($source, $item->key, $this->clock->now()));
            }
            // Déjà dans Radar (ajout d'un membre ou autre source) : l'article est seulement marqué vu (R6).
            if (null === $this->linkManager->findDuplicate($item->url)) {
                $this->linkManager->publish($source, $item->url, $poster);
                ++$published;
            }
        }

        $source->markFetched($this->clock->now());
        $this->entityManager->flush();

        return $published;
    }
}
