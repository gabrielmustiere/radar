<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Service\AdminManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Relit la liste à chaque décision : un admin ajouté ou retiré l'est tout de suite, sans reconnexion.
 * Pas de cache en propriété : en mode worker, il survivrait d'une requête à l'autre.
 *
 * @extends Voter<string, mixed>
 */
final class AdminVoter extends Voter
{
    public const string ADMIN = 'RADAR_ADMIN';

    public function __construct(
        private readonly AdminManager $adminManager,
    ) {}

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ADMIN === $attribute;
    }

    #[\Override]
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        #[\SensitiveParameter]
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $user = $token->getUser();

        return $user instanceof User && $this->adminManager->isAdmin($user->getUserIdentifier());
    }
}
