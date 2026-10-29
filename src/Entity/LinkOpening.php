<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LinkOpeningRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ouverture d'un lien d'origine depuis Radar : matière de la North Star (liens de plus de 30 jours rouverts).
 */
#[ORM\Entity(repositoryClass: LinkOpeningRepository::class)]
#[ORM\Index(name: 'IDX_LINK_OPENING_OPENED_AT', fields: ['openedAt'])]
class LinkOpening
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'link_id', nullable: false, onDelete: 'CASCADE')]
    private Link $link;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'opened_by_id', nullable: false, onDelete: 'CASCADE')]
    private User $openedBy;

    #[ORM\Column(name: 'opened_at')]
    private \DateTimeImmutable $openedAt;

    #[ORM\Column(name: 'link_age_days')]
    private int $linkAgeDays;

    public function __construct(Link $link, User $openedBy, \DateTimeImmutable $openedAt, int $linkAgeDays)
    {
        $this->link = $link;
        $this->openedBy = $openedBy;
        $this->openedAt = $openedAt;
        $this->linkAgeDays = $linkAgeDays;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLink(): Link
    {
        return $this->link;
    }

    public function getOpenedBy(): User
    {
        return $this->openedBy;
    }

    public function getOpenedAt(): \DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function getLinkAgeDays(): int
    {
        return $this->linkAgeDays;
    }
}
