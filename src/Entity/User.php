<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Type\Team;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_EXTENSION_KEY_HASH', fields: ['extensionKeyHash'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** @var non-empty-string|null */
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    /** Empreinte SHA-256 de la clé de l'extension navigateur : la clé en clair n'est jamais conservée. */
    #[ORM\Column(name: 'extension_key_hash', length: 64, nullable: true)]
    private ?string $extensionKeyHash = null;

    #[ORM\Column(name: 'extension_key_created_at', nullable: true)]
    private ?\DateTimeImmutable $extensionKeyCreatedAt = null;

    #[ORM\Column(name: 'extension_key_last_used_at', nullable: true)]
    private ?\DateTimeImmutable $extensionKeyLastUsedAt = null;

    #[ORM\Column(nullable: true, enumType: Team::class)]
    private ?Team $team = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $admin = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /** @param non-empty-string $email */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @see UserInterface
     */
    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->email ?? throw new \LogicException('User email is not set.');
    }

    public function getDisplayName(): string
    {
        $identifier = $this->getUserIdentifier();
        $localPart = strstr($identifier, needle: '@', before_needle: true);

        return false === $localPart ? $identifier : $localPart;
    }

    /**
     * @see UserInterface
     */
    #[\Override]
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    #[\Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(#[\SensitiveParameter] string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getExtensionKeyHash(): ?string
    {
        return $this->extensionKeyHash;
    }

    public function getExtensionKeyCreatedAt(): ?\DateTimeImmutable
    {
        return $this->extensionKeyCreatedAt;
    }

    public function getExtensionKeyLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->extensionKeyLastUsedAt;
    }

    public function replaceExtensionKey(string $hash, \DateTimeImmutable $createdAt): void
    {
        $this->extensionKeyHash = $hash;
        $this->extensionKeyCreatedAt = $createdAt;
        $this->extensionKeyLastUsedAt = null;
    }

    public function revokeExtensionKey(): void
    {
        $this->extensionKeyHash = null;
        $this->extensionKeyCreatedAt = null;
        $this->extensionKeyLastUsedAt = null;
    }

    public function markExtensionKeyUsed(\DateTimeImmutable $usedAt): void
    {
        $this->extensionKeyLastUsedAt = $usedAt;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): static
    {
        $this->admin = $admin;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    /**
     * Remplace la comparaison par défaut du `ContextListener` : `$this` est le compte lu en session, `$user` celui
     * rechargé depuis la base. Un compte désactivé fait tomber la session. Les rôles ne sont pas comparés : ils valent
     * toujours `ROLE_USER`, le statut admin passant par `AdminVoter`.
     */
    #[\Override]
    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self || !$user->isActive()) {
            return false;
        }

        if ($user->getUserIdentifier() !== $this->getUserIdentifier()) {
            return false;
        }

        $sessionPassword = $this->password ?? '';
        $refreshedPassword = $user->getPassword() ?? '';

        return (
            hash_equals($refreshedPassword, $sessionPassword)
            || hash_equals(hash('crc32c', $refreshedPassword), $sessionPassword)
        );
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     *
     * @return array<array-key, mixed>
     */
    public function __serialize(): array
    {
        // Le cast garde les clés de propriétés privées préfixées, attendues par la désérialisation native.
        // @mago-expect analysis:invalid-type-cast
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = null !== $this->password ? hash('crc32c', $this->password) : null;

        return $data;
    }
}
