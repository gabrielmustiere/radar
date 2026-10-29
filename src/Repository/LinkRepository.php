<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Link;
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

    /** @return WindowPage<Link> */
    public function findLatest(int $page = 1): WindowPage
    {
        return $this->paginate(
            $this
                ->createQueryBuilder('l')
                ->addSelect('u', 'c')
                ->join('l.postedBy', 'u')
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

    public function save(Link $link): void
    {
        $this->getEntityManager()->persist($link);
        $this->getEntityManager()->flush();
    }

    /** @return WindowPage<Link> liens de la catégorie, chacun avec toutes ses catégories */
    public function findByCategory(Category $category, int $page = 1): WindowPage
    {
        return $this->paginate(
            $this
                ->createQueryBuilder('l')
                ->addSelect('u', 'c')
                ->join('l.postedBy', 'u')
                ->join('l.categories', 'filter', 'WITH', 'filter = :category')
                ->leftJoin('l.categories', 'c')
                ->setParameter('category', $category)
                ->orderBy('l.postedAt', 'DESC')
                ->addOrderBy('l.id', 'DESC'),
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

    /** @return WindowPage<Link> liens contenant chacun des mots, dans un de leurs champs texte ou une de leurs catégories */
    public function search(SearchQuery $query, int $page = 1): WindowPage
    {
        if ($query->isEmpty()) {
            return new WindowPage([], 0, Window::fromPageNumberAndSize(1, self::PER_PAGE));
        }

        $builder = $this
            ->createQueryBuilder('l')
            ->addSelect('u', 'c')
            ->join('l.postedBy', 'u')
            ->leftJoin('l.categories', 'c')
            ->orderBy('l.postedAt', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->addOrderBy('c.name', 'ASC');

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

        return $this->paginate($builder, $page);
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
