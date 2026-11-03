<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\Type\FeedType;
use App\Enum\Type\SourceType;
use App\Enum\Type\Team;
use App\Search\LinkFilter;

/**
 * Compteurs de la barre de filtres en entonnoir : l'appelant retire la dimension comptée du filtre,
 * pour que chaque option compte les liens qu'on obtiendrait en la choisissant.
 */
final readonly class LinkFacetRepository
{
    public function __construct(
        private LinkRepository $linkRepository,
    ) {}

    public function countMatching(LinkFilter $filter): int
    {
        return (int) $this->linkRepository
            ->filtered($filter)
            ->select('COUNT(DISTINCT l.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return array<string, int> nombre de liens répondant aux filtres par équipe (valeur de `Team`) */
    public function countByTeam(LinkFilter $filter): array
    {
        /** @var list<array{team: Team|string, linkCount: int|string}> $rows */
        $rows = $this->linkRepository
            ->filtered($filter)
            ->select('tc.team AS team', 'COUNT(DISTINCT l.id) AS linkCount')
            ->join('l.categories', 'tc')
            ->andWhere('tc.team IS NOT NULL')
            ->groupBy('tc.team')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['team'] instanceof Team ? $row['team']->value : $row['team']] = (int) $row['linkCount'];
        }

        return $counts;
    }

    /** @return array<int, int> nombre de liens répondant aux filtres par catégorie (id), limité aux catégories de l'équipe filtrée */
    public function countByCategory(LinkFilter $filter): array
    {
        $builder = $this->linkRepository
            ->filtered($filter)
            ->select('grouped.id AS id', 'COUNT(DISTINCT l.id) AS linkCount')
            ->join('l.categories', 'grouped')
            ->groupBy('grouped.id');
        if (null !== $filter->team) {
            $builder->andWhere('grouped.team = :team');
        }

        /** @var list<array{id: int|string, linkCount: int|string}> $rows */
        $rows = $builder->getQuery()->getArrayResult();

        return array_map(intval(...), array_column($rows, 'linkCount', 'id'));
    }

    /** @return array<string, int> nombre de liens répondant aux filtres par type (valeur de `FeedType`) ; les images n'en ont pas */
    public function countByType(LinkFilter $filter): array
    {
        /** @var list<array{sourceType: SourceType|string, source: int|string|null, linkCount: int|string}> $rows */
        $rows = $this->linkRepository
            ->filtered($filter)
            ->select('l.sourceType AS sourceType', 'IDENTITY(l.source) AS source', 'COUNT(DISTINCT l.id) AS linkCount')
            ->groupBy('l.sourceType')
            ->addGroupBy('l.source')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $sourceType = $row['sourceType'] instanceof SourceType
                ? $row['sourceType']
                : SourceType::from($row['sourceType']);
            $type = null === $row['source'] ? FeedType::fromSourceType($sourceType) : FeedType::Followed;
            if (null !== $type) {
                $counts[$type->value] = ($counts[$type->value] ?? 0) + (int) $row['linkCount'];
            }
        }

        return $counts;
    }
}
