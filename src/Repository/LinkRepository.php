<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Link;
use App\Enum\Type\FeedType;
use App\Enum\Type\PreviewStatus;
use App\Enum\Type\Team;
use App\Search\LinkFilter;
use App\Search\SearchQuery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\OffsetPaginator;
use Doctrine\ORM\Tools\Pagination\Window;
use Doctrine\ORM\Tools\Pagination\WindowPage;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Link>
 */
class LinkRepository extends ServiceEntityRepository
{
    public const int PER_PAGE = 24;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Link::class);
    }

    /** @return WindowPage<Link> liens répondant à tous les filtres, plus récents d'abord, chacun avec toutes ses catégories */
    public function findFiltered(LinkFilter $filter, int $page = 1): WindowPage
    {
        return $this->paginate(
            $this
                ->filtered($filter)
                ->addSelect('u', 'c', 's')
                ->join('l.postedBy', 'u')
                ->leftJoin('l.source', 's')
                ->leftJoin('l.categories', 'c')
                ->orderBy('l.postedAt', 'DESC')
                ->addOrderBy('l.id', 'DESC'),
            $page,
        );
    }

    public function findOneByNormalizedUrl(string $normalizedUrl): ?Link
    {
        return $this->findOneBy(['normalizedUrl' => $normalizedUrl]);
    }

    /**
     * Liens dont l'aperçu est à (re)construire : jamais tenté, prêt depuis avant `$readyBefore`,
     * ou indisponible depuis avant `$unavailableBefore`. Un lien en attente a déjà son message en file.
     *
     * @return list<Link>
     */
    public function findDueForEnrichment(\DateTimeImmutable $readyBefore, \DateTimeImmutable $unavailableBefore): array
    {
        /** @var list<Link> */
        return $this
            ->createQueryBuilder('l')
            ->where('l.previewFetchedAt IS NULL AND l.previewStatus != :pending')
            ->orWhere('l.previewStatus = :ready AND l.previewFetchedAt < :readyBefore')
            ->orWhere('l.previewStatus = :unavailable AND l.previewFetchedAt < :unavailableBefore')
            ->setParameter('pending', PreviewStatus::Pending)
            ->setParameter('ready', PreviewStatus::Ready)
            ->setParameter('readyBefore', $readyBefore)
            ->setParameter('unavailable', PreviewStatus::Unavailable)
            ->setParameter('unavailableBefore', $unavailableBefore)
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Link $link): void
    {
        $this->getEntityManager()->persist($link);
        $this->getEntityManager()->flush();
    }

    /** @return list<array{id: int, name: string, team: ?Team, linkCount: int}> catégories portant au moins un lien */
    public function countByCategory(): array
    {
        /** @var list<array{id: int|string, name: string, team: ?Team, linkCount: int|string}> $rows */
        $rows = $this
            ->createQueryBuilder('l')
            ->select('c.id', 'c.name', 'c.team', 'COUNT(l.id) AS linkCount')
            ->join('l.categories', 'c')
            ->groupBy('c.id')
            ->addGroupBy('c.name')
            ->addGroupBy('c.team')
            ->getQuery()
            ->getArrayResult();

        $categories = [];
        foreach ($rows as $row) {
            $categories[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'team' => $row['team'],
                'linkCount' => (int) $row['linkCount'],
            ];
        }

        return $categories;
    }

    /** Liens répondant aux filtres, base de la liste et des compteurs de `LinkFacetRepository`. */
    public function filtered(LinkFilter $filter): QueryBuilder
    {
        $builder = $this->filter($this->createQueryBuilder('l'), $filter->query);
        if (null !== $filter->category) {
            $builder->join('l.categories', 'filter', 'WITH', 'filter = :category')->setParameter(
                'category',
                $filter->category,
            );
        }

        if (null !== $filter->team) {
            $teamMatch = $this
                ->getEntityManager()
                ->createQueryBuilder()
                ->select('1')
                ->from(Link::class, 'lt')
                ->join('lt.categories', 'ct')
                ->where('lt = l AND ct.team = :team')
                ->getDQL();
            $builder->andWhere(sprintf('EXISTS(%s)', $teamMatch))->setParameter('team', $filter->team);
        }

        if (FeedType::Followed === $filter->type) {
            $builder->andWhere('l.source IS NOT NULL');
        }

        $sourceType = $filter->type?->sourceType();
        if (null !== $sourceType) {
            $builder->andWhere('l.source IS NULL AND l.sourceType = :sourceType')->setParameter(
                'sourceType',
                $sourceType,
            );
        }

        return $builder;
    }

    /** Chaque mot doit se retrouver dans un des champs texte du lien ou une de ses catégories. */
    private function filter(QueryBuilder $builder, SearchQuery $query): QueryBuilder
    {
        foreach ($query->words as $index => $word) {
            $parameter = 'word' . $index;
            $categoryMatch = $this
                ->getEntityManager()
                ->createQueryBuilder()
                ->select('1')
                ->from(Link::class, 'l' . $index)
                ->join('l' . $index . '.categories', 'c' . $index)
                ->where(sprintf('l%1$d = l AND c%1$d.name LIKE :%2$s ESCAPE \'!\'', $index, $parameter))
                ->getDQL();

            $builder->andWhere(sprintf(
                "l.title LIKE :%1\$s ESCAPE '!' OR l.description LIKE :%1\$s ESCAPE '!'"
                . " OR l.context LIKE :%1\$s ESCAPE '!' OR l.url LIKE :%1\$s ESCAPE '!' OR EXISTS(%2\$s)",
                $parameter,
                $categoryMatch,
            ))->setParameter($parameter, '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $word) . '%');
        }

        return $builder;
    }

    /**
     * La pagination par sous-requête garde des cartes complètes : un LIMIT brut tronquerait la collection de catégories jointe.
     *
     * @return WindowPage<Link>
     */
    private function paginate(QueryBuilder $builder, int $page): WindowPage
    {
        /** @var OffsetPaginator<Link> $paginator */
        $paginator = new OffsetPaginator(fetchJoinCollection: true);

        return $paginator->paginate($builder, Window::fromPageNumberAndSize($page, self::PER_PAGE));
    }
}
