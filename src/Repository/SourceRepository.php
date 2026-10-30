<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Source;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Source>
 */
class SourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Source::class);
    }

    /** Active ou retirée : une adresse n'a qu'une source. */
    public function findOneByFeedUrl(string $feedUrl): ?Source
    {
        return $this->findOneBy(['feedUrl' => $feedUrl]);
    }

    /** @return list<Source> avec leurs catégories */
    public function findActiveOrderedByName(): array
    {
        /** @var list<Source> */
        return $this
            ->createQueryBuilder('s')
            ->addSelect('c')
            ->leftJoin('s.categories', 'c')
            ->where('s.removedAt IS NULL')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<int> */
    public function findActiveIds(): array
    {
        /** @var list<int> */
        return $this
            ->createQueryBuilder('s')
            ->select('s.id')
            ->where('s.removedAt IS NULL')
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function save(Source $source): void
    {
        $this->getEntityManager()->persist($source);
        $this->getEntityManager()->flush();
    }
}
