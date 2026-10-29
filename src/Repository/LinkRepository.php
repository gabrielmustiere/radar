<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Link;
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
}
