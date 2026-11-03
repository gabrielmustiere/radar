<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Enum\Type\Team;
use App\Exception\EmailAlreadyUsedException;
use App\Exception\ProtectedAccountException;
use App\Form\Model\UserEdition;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Comptes membres : créés par un admin depuis le back office, ou par la commande `app:user:create` pour le premier
 * compte d'une installation vide (les fixtures purgent la base).
 */
final readonly class UserManager
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private AdminManager $adminManager,
    ) {}

    /** @throws EmailAlreadyUsedException quand l'email a déjà un compte, quelle que soit sa casse */
    public function create(string $email, #[\SensitiveParameter] string $plainPassword, ?Team $team, bool $admin): User
    {
        $email = $this->normalizeEmail($email);
        if (null !== $this->userRepository->findOneByEmailIgnoringCase($email)) {
            throw new EmailAlreadyUsedException();
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);
        $user->setTeam($team);
        $user->setAdmin($admin);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->userRepository->save($user);

        return $user;
    }

    /**
     * @throws EmailAlreadyUsedException quand l'email est celui d'un autre compte, quelle que soit sa casse
     * @throws ProtectedAccountException quand l'admin se retire le statut admin, ou le retire à l'admin permanent
     */
    public function update(User $user, UserEdition $edition, User $actor): void
    {
        $email = $this->normalizeEmail((string) $edition->email);

        $owner = $this->userRepository->findOneByEmailIgnoringCase($email);
        if (null !== $owner && $owner !== $user) {
            throw new EmailAlreadyUsedException();
        }

        if (!$edition->admin && $this->adminManager->isPermanent($user)) {
            throw new ProtectedAccountException('L\'admin permanent garde toujours le statut admin.');
        }

        if (!$edition->admin && $this->isSelf($user, $actor) && $this->adminManager->isAdmin($user)) {
            throw new ProtectedAccountException('Vous ne pouvez pas vous retirer le statut admin.');
        }

        $user->setEmail($email);
        $user->setTeam($edition->team);
        $user->setAdmin($edition->admin);
        $this->userRepository->save($user);
    }

    public function changePassword(User $user, #[\SensitiveParameter] string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->userRepository->save($user);
    }

    /** @throws ProtectedAccountException quand l'admin se désactive lui-même, ou désactive l'admin permanent */
    public function deactivate(User $user, User $actor): void
    {
        if ($this->adminManager->isPermanent($user)) {
            throw new ProtectedAccountException('L\'admin permanent ne peut pas être désactivé.');
        }

        if ($this->isSelf($user, $actor)) {
            throw new ProtectedAccountException('Vous ne pouvez pas désactiver votre propre compte.');
        }

        $user->deactivate();
        $this->userRepository->save($user);
    }

    public function activate(User $user): void
    {
        $user->activate();
        $this->userRepository->save($user);
    }

    /** @return non-empty-string */
    private function normalizeEmail(string $email): string
    {
        $email = mb_strtolower(trim($email));
        if ('' === $email) {
            throw new \InvalidArgumentException('L\'email d\'un compte ne peut pas être vide.');
        }

        return $email;
    }

    private function isSelf(User $user, User $actor): bool
    {
        return $user->getUserIdentifier() === $actor->getUserIdentifier();
    }
}
