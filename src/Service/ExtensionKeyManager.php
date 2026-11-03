<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Psr\Clock\ClockInterface;

/**
 * Clé personnelle de l'extension navigateur : une au plus par membre, connue en clair seulement à sa génération,
 * expirée après 90 jours sans usage.
 */
final readonly class ExtensionKeyManager
{
    public const string PREFIX = 'rdr_';

    private const string MAX_IDLE = '+90 days';

    public function __construct(
        private UserRepository $userRepository,
        private ClockInterface $clock,
    ) {}

    /** Remplace la clé précédente, qui cesse aussitôt de fonctionner. */
    public function generate(User $user): string
    {
        $key = self::PREFIX . bin2hex(random_bytes(32));
        $user->replaceExtensionKey(self::hash($key), $this->clock->now());
        $this->userRepository->save($user);

        return $key;
    }

    public function revoke(User $user): void
    {
        $user->revokeExtensionKey();
        $this->userRepository->save($user);
    }

    /**
     * Propriétaire d'une clé valide, dont l'usage repousse l'expiration ; null pour une clé inconnue, expirée ou dont le
     * compte est désactivé. Ce dernier cas est refusé ici et non par le seul `UserChecker`, qui passe après : sans quoi
     * chaque appel refusé repousserait l'expiration de la clé d'un compte désactivé.
     */
    public function authenticate(#[\SensitiveParameter] string $key): ?User
    {
        $user = $this->userRepository->findOneByExtensionKeyHash(self::hash($key));
        if (null === $user || !$user->isActive() || $this->isExpired($user)) {
            return null;
        }

        $user->markExtensionKeyUsed($this->clock->now());
        $this->userRepository->save($user);

        return $user;
    }

    public function isExpired(User $user): bool
    {
        $lastActivity = $user->getExtensionKeyLastUsedAt() ?? $user->getExtensionKeyCreatedAt();

        return null === $lastActivity || $lastActivity->modify(self::MAX_IDLE) < $this->clock->now();
    }

    private static function hash(#[\SensitiveParameter] string $key): string
    {
        return hash('sha256', $key);
    }
}
