<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Category;
use App\Entity\Link;
use App\Enum\Type\FeedType;
use App\Enum\Type\Team;
use App\Repository\CategoryRepository;
use App\Repository\LinkFacetRepository;
use App\Repository\LinkRepository;
use App\Search\LinkFilter;
use App\Search\SearchQuery;
use Doctrine\ORM\Tools\Pagination\WindowPage;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Liste de cartes (fil ou catégorie) filtrée en entonnoir : recherche de la topbar, équipe, catégorie (la route) et type.
 * `q`, `team`, `type` et `page` restent dans l'URL ; les pastilles de filtre sont des liens.
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

    #[LiveProp(url: true)]
    public ?Team $team = null;

    #[LiveProp(url: true)]
    public ?FeedType $type = null;

    /**
     * Fournie au premier rendu par le contrôleur (qui a vérifié la page), recalculée aux rendus suivants.
     *
     * @var WindowPage<Link>|null
     */
    public ?WindowPage $links = null;

    public function __construct(
        private readonly LinkRepository $linkRepository,
        private readonly LinkFacetRepository $facetRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function resetPage(): void
    {
        $this->page = null;
    }

    public function getQuery(): SearchQuery
    {
        return SearchQuery::fromString($this->q);
    }

    public function getFilter(): LinkFilter
    {
        return new LinkFilter($this->getQuery(), $this->team, $this->category, $this->type);
    }

    public function isFiltered(): bool
    {
        return (
            null !== $this->team
            || null !== $this->category
            || null !== $this->type
            || !$this->getQuery()->isEmpty()
        );
    }

    /** @return WindowPage<Link> */
    public function getResults(): WindowPage
    {
        return $this->links ??= $this->linkRepository->findFiltered($this->getFilter(), $this->page ?? 1);
    }

    /**
     * Compteurs en entonnoir : chaque option compte les liens qu'on obtiendrait en la choisissant, les autres filtres restant actifs.
     *
     * @return list<array{team: ?Team, count: int}>
     */
    public function getTeamOptions(): array
    {
        $filter = $this->getFilter()->withoutTeam();
        $counts = $this->facetRepository->countByTeam($filter);

        $options = [['team' => null, 'count' => $this->facetRepository->countMatching($filter)]];
        foreach (Team::cases() as $team) {
            $options[] = ['team' => $team, 'count' => $counts[$team->value] ?? 0];
        }

        return $options;
    }

    /**
     * Catégories de l'équipe filtrée (toutes sans équipe), dans l'ordre alphabétique français :
     * SQLite trie à l'octet (majuscules avant minuscules, accents après « z », « 10 » avant « 2 »).
     *
     * @return list<array{category: ?Category, count: int}>
     */
    public function getCategoryOptions(): array
    {
        $filter = $this->getFilter()->withoutCategory();
        $counts = $this->facetRepository->countByCategory($filter);

        $categories = array_values(array_filter(
            $this->categoryRepository->findAllOrderedByName(),
            fn(Category $category): bool => null === $this->team || $category->getTeam() === $this->team,
        ));
        $collator = new \Collator('fr_FR');
        $collator->setAttribute(\Collator::NUMERIC_COLLATION, \Collator::ON);
        usort(
            $categories,
            static fn(Category $a, Category $b): int => (int) $collator->compare($a->getName(), $b->getName()),
        );

        $options = [['category' => null, 'count' => $this->facetRepository->countMatching($filter)]];
        foreach ($categories as $category) {
            $options[] = ['category' => $category, 'count' => $counts[(int) $category->getId()] ?? 0];
        }

        return $options;
    }

    /** @return list<array{type: ?FeedType, count: int}> */
    public function getTypeOptions(): array
    {
        $filter = $this->getFilter()->withoutType();
        $counts = $this->facetRepository->countByType($filter);

        $options = [['type' => null, 'count' => $this->facetRepository->countMatching($filter)]];
        foreach (FeedType::cases() as $type) {
            $options[] = ['type' => $type, 'count' => $counts[$type->value] ?? 0];
        }

        return $options;
    }
}
