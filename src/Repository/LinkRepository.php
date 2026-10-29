<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Link;
use App\Search\SearchQuery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Link>
 */
class LinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Link::class);
    }

    /** @return list<Link> */
    public function findLatest(): array
    {
        /** @var list<Link> */
        return $this
            ->createQueryBuilder('l')
            ->addSelect('u', 'c')
            ->join('l.postedBy', 'u')
            ->leftJoin('l.categories', 'c')
            ->orderBy('l.postedAt', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
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

    /** @return list<Link> liens de la catégorie, chacun avec toutes ses catégories */
    public function findByCategory(Category $category): array
    {
        /** @var list<Link> */
        return $this
            ->createQueryBuilder('l')
            ->addSelect('u', 'c')
            ->join('l.postedBy', 'u')
            ->join('l.categories', 'filter', 'WITH', 'filter = :category')
            ->leftJoin('l.categories', 'c')
            ->setParameter('category', $category)
            ->orderBy('l.postedAt', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
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

    /** @return list<Link> liens contenant chacun des mots, dans un de leurs champs texte ou une de leurs catégories */
    public function search(SearchQuery $query): array
    {
        if ($query->isEmpty()) {
            return [];
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

        /** @var list<Link> */
        return $builder->getQuery()->getResult();
    }
}
