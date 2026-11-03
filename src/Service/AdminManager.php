<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Admins : les comptes au statut admin, plus un admin permanent désigné par configuration, que l'interface ne peut ni
 * désactiver ni priver du statut (R13).
 */
final readonly class AdminManager
{
    private string $permanentEmail;

    public function __construct(#[Autowire(env: 'RADAR_ADMIN_EMAIL')] string $permanentEmail)
    {
        $this->permanentEmail = mb_strtolower(trim($permanentEmail));
    }

    public function isAdmin(User $user): bool
    {
        return $user->isAdmin() || $this->isPermanent($user);
    }

    public function isPermanent(User $user): bool
    {
        return '' !== $this->permanentEmail && mb_strtolower($user->getUserIdentifier()) === $this->permanentEmail;
    }
}
