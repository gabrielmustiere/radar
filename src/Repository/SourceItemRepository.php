<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Source;
use App\Entity\SourceItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SourceItem>
 */
class SourceItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SourceItem::class);
    }

    /**
     * @param list<string> $keys
     *
     * @return list<string> celles de ces clés déjà vues pour la source
     */
    public function findSeenKeys(Source $source, array $keys): array
    {
        if ([] === $keys) {
            return [];
        }

        /** @var list<string> */
        return $this
            ->createQueryBuilder('i')
            ->select('i.itemKey')
            ->where('i.source = :source')
            ->andWhere('i.itemKey IN (:keys)')
            ->setParameter('source', $source)
            ->setParameter('keys', $keys)
            ->getQuery()
            ->getSingleColumnResult();
    }
}
