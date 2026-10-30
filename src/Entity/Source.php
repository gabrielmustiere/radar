<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Flux suivi. Retirée, une source reste en base : ses liens gardent son nom, et la même adresse la réactive.
 */
#[ORM\Entity(repositoryClass: SourceRepository::class)]
#[ORM\Index(name: 'IDX_SOURCE_REMOVED_AT', fields: ['removedAt'])]
#[ORM\UniqueConstraint(name: 'UNIQ_SOURCE_FEED_URL', fields: ['feedUrl'])]
class Source
{
    private const int ERROR_MAX_LENGTH = 255;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'feed_url', length: 2048)]
    private string $feedUrl;

    #[ORM\Column(length: 255)]
    private string $name;

    /** @var Collection<int, Category> */
    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: 'source_category')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $categories;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'removed_at', nullable: true)]
    private ?\DateTimeImmutable $removedAt = null;

    #[ORM\Column(name: 'last_fetched_at', nullable: true)]
    private ?\DateTimeImmutable $lastFetchedAt = null;

    #[ORM\Column(name: 'last_error', length: 255, nullable: true)]
    private ?string $lastError = null;

    #[ORM\Column(name: 'last_error_at', nullable: true)]
    private ?\DateTimeImmutable $lastErrorAt = null;

    /** @param iterable<Category> $categories */
    public function __construct(string $feedUrl, string $name, iterable $categories, \DateTimeImmutable $createdAt)
    {
        $this->feedUrl = $feedUrl;
        $this->name = $name;
        $this->categories = new ArrayCollection();
        $this->replaceCategories($categories);
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFeedUrl(): string
    {
        return $this->feedUrl;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return Collection<int, Category> */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRemovedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function isActive(): bool
    {
        return null === $this->removedAt;
    }

    public function getLastFetchedAt(): ?\DateTimeImmutable
    {
        return $this->lastFetchedAt;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getLastErrorAt(): ?\DateTimeImmutable
    {
        return $this->lastErrorAt;
    }

    public function remove(\DateTimeImmutable $removedAt): void
    {
        $this->removedAt = $removedAt;
    }

    /**
     * Repart comme une source neuve : l'état de relève d'avant le retrait ne dit plus rien.
     *
     * @param iterable<Category> $categories
     */
    public function reactivate(string $name, iterable $categories): void
    {
        $this->name = $name;
        $this->replaceCategories($categories);
        $this->removedAt = null;
        $this->lastFetchedAt = null;
        $this->lastError = null;
        $this->lastErrorAt = null;
    }

    public function markFetched(\DateTimeImmutable $fetchedAt): void
    {
        $this->lastFetchedAt = $fetchedAt;
    }

    /** La dernière erreur reste affichée après une relève réussie : sa date dit si elle est ancienne. */
    public function markFailed(string $error, \DateTimeImmutable $failedAt): void
    {
        $this->lastError = mb_substr($error, start: 0, length: self::ERROR_MAX_LENGTH);
        $this->lastErrorAt = $failedAt;
    }

    /** @param iterable<Category> $categories */
    private function replaceCategories(iterable $categories): void
    {
        $this->categories->clear();
        foreach ($categories as $category) {
            $this->categories->add($category);
        }
    }
}
