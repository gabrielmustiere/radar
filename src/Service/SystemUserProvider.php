<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Compte « radar », posteur des articles publiés par les sources suivies. Il naît à la première publication
 * (les fixtures purgent les utilisateurs) et ne peut pas se connecter : son mot de passe n'est le hash de rien.
 */
final readonly class SystemUserProvider
{
    public const string EMAIL = 'radar@radar.invalid';

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /** Persisté au prochain flush de l'appelant s'il n'existait pas. */
    public function radar(): User
    {
        $user = $this->userRepository->findOneByEmail(self::EMAIL);
        if (null !== $user) {
            return $user;
        }

        $user = new User();
        $user->setEmail(self::EMAIL);
        $user->setPassword('!');
        $this->entityManager->persist($user);

        return $user;
    }
}
