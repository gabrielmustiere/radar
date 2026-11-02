<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /** @return list<Category> */
    public function findAllOrderedByName(): array
    {
        /** @var list<Category> */
        return $this->createQueryBuilder('c')->orderBy('c.name', 'ASC')->getQuery()->getResult();
    }

    /**
     * @param list<int|string> $ids
     *
     * @return list<Category>
     */
    public function findByIdsOrderedByName(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        /** @var list<Category> */
        return $this
            ->createQueryBuilder('c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Category $category): void
    {
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
    }
}
