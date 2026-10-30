<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Link;
use App\Entity\Source;
use App\Entity\User;
use App\Exception\DuplicateLinkException;
use App\Form\Model\HistoricalLinkSubmission;
use App\Form\Model\LinkSubmission;
use App\Message\FetchLinkPreview;
use App\Repository\LinkRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class LinkManager
{
    public function __construct(
        private LinkRepository $linkRepository,
        private LinkUrlAnalyzer $urlAnalyzer,
        private CategoryResolver $categoryResolver,
        private ClockInterface $clock,
        private MessageBusInterface $messageBus,
    ) {}

    /** @throws DuplicateLinkException quand le lien existe déjà : rien n'est créé */
    public function add(LinkSubmission $submission, User $poster): Link
    {
        return $this->create($submission, $poster, $this->clock->now(), seeded: false);
    }

    /**
     * Reprise d'un lien de l'historique Chat : date et posteur d'origine, jamais annoncé (R5).
     *
     * @throws DuplicateLinkException quand le lien existe déjà : rien n'est créé
     */
    public function seed(HistoricalLinkSubmission $submission): Link
    {
        if (null === $submission->poster || null === $submission->postedAt) {
            throw new \LogicException('Une reprise validée porte toujours son posteur et sa date d\'origine.');
        }

        return $this->create($submission, $submission->poster, $submission->postedAt, seeded: true);
    }

    /**
     * Article d'une source suivie : classé dans les catégories de la source, daté de la relève, sans contexte.
     *
     * @throws DuplicateLinkException quand le lien existe déjà : rien n'est créé
     */
    public function publish(Source $source, string $url, User $poster): Link
    {
        $submission = new LinkSubmission();
        $submission->url = $url;
        $submission->categories = array_values($source->getCategories()->toArray());

        return $this->create($submission, $poster, $this->clock->now(), seeded: false, source: $source);
    }

    /** Lien déjà présent sous cette adresse, une fois normalisée (www, slash final, paramètres de suivi…). */
    public function findDuplicate(string $url): ?Link
    {
        return $this->linkRepository->findOneByNormalizedUrl($this->urlAnalyzer->normalize($url));
    }

    private function create(
        LinkSubmission $submission,
        User $poster,
        \DateTimeImmutable $postedAt,
        bool $seeded,
        ?Source $source = null,
    ): Link {
        $url = (string) $submission->url;
        $existing = $this->findDuplicate($url);
        if (null !== $existing) {
            throw new DuplicateLinkException($existing);
        }

        $categories = $submission->categories;
        $newCategoryName = $submission->newCategoryName();
        if (null !== $newCategoryName) {
            $category = $this->categoryResolver->resolve($newCategoryName);
            if (!in_array($category, $categories, strict: true)) {
                $categories[] = $category;
            }
        }

        $context = trim($submission->context ?? '');
        $link = new Link(
            $url,
            $this->urlAnalyzer->normalize($url),
            $categories,
            $poster,
            $postedAt,
            $this->urlAnalyzer->sourceType($url),
            '' === $context ? null : $context,
            $seeded,
            $source,
        );

        $this->linkRepository->save($link);
        $this->messageBus->dispatch(new FetchLinkPreview((int) $link->getId()));

        return $link;
    }
}
