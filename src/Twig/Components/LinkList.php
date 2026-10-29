<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Category;
use App\Entity\Link;
use App\Repository\LinkRepository;
use App\Search\SearchQuery;
use Doctrine\ORM\Tools\Pagination\WindowPage;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Liste de cartes (fil ou catégorie) filtrée par la recherche de la topbar : `q` et `page` restent dans l'URL.
 */
#[AsLiveComponent]
final class LinkList
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true, onUpdated: 'resetPage')]
    public string $q = '';

    /** Absente de l'URL tant qu'on est sur la première page. */
    #[LiveProp(url: true)]
    public ?int $page = null;

    #[LiveProp]
    public ?Category $category = null;

    /**
     * Fournie au premier rendu par le contrôleur (qui a vérifié la page), recalculée aux rendus suivants.
     *
     * @var WindowPage<Link>|null
     */
    public ?WindowPage $links = null;

    public function __construct(
        private readonly LinkRepository $linkRepository,
    ) {}

    public function resetPage(): void
    {
        $this->page = null;
    }

    public function getQuery(): SearchQuery
    {
        return SearchQuery::fromString($this->q);
    }

    /** @return WindowPage<Link> */
    public function getResults(): WindowPage
    {
        return $this->links ??= null === $this->category
            ? $this->linkRepository->findLatest($this->getQuery(), $this->page ?? 1)
            : $this->linkRepository->findByCategory($this->category, $this->getQuery(), $this->page ?? 1);
    }
}
