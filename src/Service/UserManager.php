<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Exception\EmailAlreadyUsedException;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Comptes membres : en production, ils naissent par la commande `app:user:create` (les fixtures purgent la base).
 */
final readonly class UserManager
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    /**
     * @param non-empty-string $email
     *
     * @throws EmailAlreadyUsedException quand l'email a déjà un compte
     */
    public function create(string $email, #[\SensitiveParameter] string $plainPassword): User
    {
        if (null !== $this->userRepository->findOneByEmail($email)) {
            throw new EmailAlreadyUsedException();
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->userRepository->save($user);

        return $user;
    }
}
