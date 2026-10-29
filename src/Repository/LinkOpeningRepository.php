<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LinkOpening;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LinkOpening>
 */
class LinkOpeningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LinkOpening::class);
    }

    public function save(LinkOpening $opening): void
    {
        $this->getEntityManager()->persist($opening);
        $this->getEntityManager()->flush();
    }
}
