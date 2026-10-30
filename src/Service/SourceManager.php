<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Source;
use App\Entity\SourceItem;
use App\Exception\AlreadyFollowedSourceException;
use App\Exception\InvalidFeedException;
use App\Feed\Feed;
use App\Feed\FeedReader;
use App\Form\Model\SourceSubmission;
use App\Repository\SourceItemRepository;
use App\Repository\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class SourceManager
{
    public function __construct(
        private SourceRepository $sourceRepository,
        private SourceItemRepository $sourceItemRepository,
        private FeedReader $feedReader,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {}

    /**
     * Les articles présents dans le flux à l'enregistrement ne seront jamais publiés : seuls les suivants le seront.
     * Une source retirée de même adresse est réactivée plutôt que recréée.
     *
     * @throws InvalidFeedException          quand le flux est injoignable ou illisible
     * @throws AlreadyFollowedSourceException quand une source active suit déjà cette adresse
     */
    public function register(SourceSubmission $submission): Source
    {
        $feedUrl = trim((string) $submission->feedUrl);
        $source = $this->sourceRepository->findOneByFeedUrl($feedUrl);
        if (null !== $source && $source->isActive()) {
            throw new AlreadyFollowedSourceException();
        }

        $feed = $this->feedReader->read($feedUrl);
        $name = mb_substr(
            '' !== $feed->title ? $feed->title : (string) parse_url($feedUrl, PHP_URL_HOST),
            start: 0,
            length: 255,
        );

        $source?->reactivate($name, $submission->categories);
        if (null === $source) {
            $source = new Source($feedUrl, $name, $submission->categories, $this->clock->now());
            $this->entityManager->persist($source);
        }

        $this->markAllSeen($source, $feed);
        $this->entityManager->flush();

        return $source;
    }

    public function remove(Source $source): void
    {
        $source->remove($this->clock->now());
        $this->entityManager->flush();
    }

    private function markAllSeen(Source $source, Feed $feed): void
    {
        $keys = $feed->keys();
        $seen = null === $source->getId() ? [] : $this->sourceItemRepository->findSeenKeys($source, $keys);
        foreach (array_diff($keys, $seen) as $key) {
            $this->entityManager->persist(new SourceItem($source, $key, $this->clock->now()));
        }
    }
}
