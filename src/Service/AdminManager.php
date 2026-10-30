<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AdminEmail;
use App\Exception\AlreadyAdminException;
use App\Repository\AdminEmailRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Admins des sources : la liste gérée depuis l'interface, plus un admin permanent désigné par configuration,
 * qui n'est pas une ligne de la liste et ne peut donc pas en être retiré (R13).
 */
final readonly class AdminManager
{
    private string $permanentEmail;

    public function __construct(
        private AdminEmailRepository $adminEmailRepository,
        private ClockInterface $clock,
        #[Autowire(env: 'RADAR_ADMIN_EMAIL')]
        string $permanentEmail,
    ) {
        $this->permanentEmail = mb_strtolower(trim($permanentEmail));
    }

    public function isAdmin(string $email): bool
    {
        return $this->isPermanent($email) || $this->adminEmailRepository->existsByEmail($email);
    }

    public function permanentEmail(): ?string
    {
        return '' === $this->permanentEmail ? null : $this->permanentEmail;
    }

    /** @throws AlreadyAdminException quand l'email est déjà admin, de la liste ou permanent */
    public function add(string $email): AdminEmail
    {
        if ($this->isAdmin($email)) {
            throw new AlreadyAdminException();
        }

        $adminEmail = new AdminEmail(trim($email), $this->clock->now());
        $this->adminEmailRepository->save($adminEmail);

        return $adminEmail;
    }

    public function remove(AdminEmail $adminEmail): void
    {
        $this->adminEmailRepository->remove($adminEmail);
    }

    private function isPermanent(string $email): bool
    {
        return '' !== $this->permanentEmail && mb_strtolower($email) === $this->permanentEmail;
    }
}
