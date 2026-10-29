<?php

declare(strict_types=1);

namespace App\Repository;

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
}
