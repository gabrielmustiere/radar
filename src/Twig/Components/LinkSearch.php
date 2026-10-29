<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Link;
use App\Repository\LinkRepository;
use App\Search\SearchQuery;
use Doctrine\ORM\Tools\Pagination\WindowPage;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Recherche au fil de la frappe : `q` et `page` restent dans l'URL (partage, retour arrière).
 */
#[AsLiveComponent]
final class LinkSearch
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true, onUpdated: 'resetPage')]
    public string $q = '';

    /** Absente de l'URL tant qu'on est sur la première page. */
    #[LiveProp(url: true)]
    public ?int $page = null;

    /** @var WindowPage<Link>|null */
    private ?WindowPage $links = null;

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
    public function getLinks(): WindowPage
    {
        return $this->links ??= $this->linkRepository->search($this->getQuery(), $this->page ?? 1);
    }
}
