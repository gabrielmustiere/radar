<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AdminEmail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminEmail>
 */
class AdminEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminEmail::class);
    }

    public function existsByEmail(string $email): bool
    {
        return null !== $this->findOneBy(['email' => mb_strtolower($email)]);
    }

    /** @return list<AdminEmail> */
    public function findAllOrderedByEmail(): array
    {
        /** @var list<AdminEmail> */
        return $this->createQueryBuilder('a')->orderBy('a.email', 'ASC')->getQuery()->getResult();
    }

    public function save(AdminEmail $adminEmail): void
    {
        $this->getEntityManager()->persist($adminEmail);
        $this->getEntityManager()->flush();
    }

    public function remove(AdminEmail $adminEmail): void
    {
        $this->getEntityManager()->remove($adminEmail);
        $this->getEntityManager()->flush();
    }
}
