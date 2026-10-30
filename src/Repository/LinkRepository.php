<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Link;
use App\Enum\Type\PreviewStatus;
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

    /** @return WindowPage<Link> liens contenant chacun des mots recherchés */
    public function findLatest(SearchQuery $query, int $page = 1): WindowPage
    {
        return $this->paginate(
            $this->filter(
                $this
                    ->createQueryBuilder('l')
                    ->addSelect('u', 'c')
                    ->join('l.postedBy', 'u')
                    ->leftJoin('l.categories', 'c')
                    ->orderBy('l.postedAt', 'DESC')
                    ->addOrderBy('l.id', 'DESC'),
                $query,
            ),
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

    /** @return WindowPage<Link> liens de la catégorie contenant chacun des mots recherchés, chacun avec toutes ses catégories */
    public function findByCategory(Category $category, SearchQuery $query, int $page = 1): WindowPage
    {
        return $this->paginate(
            $this->filter(
                $this
                    ->createQueryBuilder('l')
                    ->addSelect('u', 'c')
                    ->join('l.postedBy', 'u')
                    ->join('l.categories', 'filter', 'WITH', 'filter = :category')
                    ->leftJoin('l.categories', 'c')
                    ->setParameter('category', $category)
                    ->orderBy('l.postedAt', 'DESC')
                    ->addOrderBy('l.id', 'DESC'),
                $query,
            ),
            $page,
        );
    }

    /** @return list<array{id: int, name: string, linkCount: int}> catégories portant au moins un lien, par nom */
    public function countByCategory(): array
    {
        /** @var list<array{id: int|string, name: string, linkCount: int|string}> $rows */
        $rows = $this
            ->createQueryBuilder('l')
            ->select('c.id', 'c.name', 'COUNT(l.id) AS linkCount')
            ->join('l.categories', 'c')
            ->groupBy('c.id')
            ->addGroupBy('c.name')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $categories = [];
        foreach ($rows as $row) {
            $categories[] = ['id' => (int) $row['id'], 'name' => $row['name'], 'linkCount' => (int) $row['linkCount']];
        }

        return $categories;
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
