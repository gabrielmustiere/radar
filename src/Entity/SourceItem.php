<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SourceItemRepository;
use Doctrine\ORM\Mapping as ORM;

/** Article déjà vu d'une source : il n'est jamais publié une seconde fois, même modifié ou réordonné dans le flux. */
#[ORM\Entity(repositoryClass: SourceItemRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_SOURCE_ITEM', fields: ['source', 'itemKey'])]
class SourceItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'source_id', nullable: false, onDelete: 'CASCADE')]
    private Source $source;

    #[ORM\Column(name: 'item_key', length: 2048)]
    private string $itemKey;

    #[ORM\Column(name: 'seen_at')]
    private \DateTimeImmutable $seenAt;

    public function __construct(Source $source, string $itemKey, \DateTimeImmutable $seenAt)
    {
        $this->source = $source;
        $this->itemKey = $itemKey;
        $this->seenAt = $seenAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function getItemKey(): string
    {
        return $this->itemKey;
    }

    public function getSeenAt(): \DateTimeImmutable
    {
        return $this->seenAt;
    }
}
