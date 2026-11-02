<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Service\ExtensionKeyManager;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final readonly class ExtensionKeyHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private ExtensionKeyManager $extensionKeyManager,
    ) {}

    #[\Override]
    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $user = $this->extensionKeyManager->authenticate($accessToken);
        if (null === $user) {
            throw new BadCredentialsException('Clé d\'extension invalide, révoquée ou expirée.');
        }

        return new UserBadge($user->getUserIdentifier(), static fn(): User => $user);
    }
}
