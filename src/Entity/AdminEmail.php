<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AdminEmailRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminEmailRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_ADMIN_EMAIL', fields: ['email'])]
class AdminEmail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $email, \DateTimeImmutable $createdAt)
    {
        $this->email = mb_strtolower($email);
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
