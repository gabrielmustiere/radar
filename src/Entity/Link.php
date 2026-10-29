<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LinkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LinkRepository::class)]
#[ORM\Index(name: 'IDX_LINK_POSTED_AT', fields: ['postedAt'])]
class Link
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 2048)]
    private string $url;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $context;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'posted_by_id', nullable: false, onDelete: 'RESTRICT')]
    private User $postedBy;

    #[ORM\Column(name: 'posted_at')]
    private \DateTimeImmutable $postedAt;

    /** @var Collection<int, Category> */
    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: 'link_category')]
    private Collection $categories;

    /** @param iterable<Category> $categories */
    public function __construct(
        string $url,
        iterable $categories,
        User $postedBy,
        \DateTimeImmutable $postedAt,
        ?string $context = null,
    ) {
        $this->url = $url;
        $this->categories = new ArrayCollection();
        foreach ($categories as $category) {
            $this->categories->add($category);
        }
        $this->postedBy = $postedBy;
        $this->postedAt = $postedAt;
        $this->context = $context;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function getPostedBy(): User
    {
        return $this->postedBy;
    }

    public function getPostedAt(): \DateTimeImmutable
    {
        return $this->postedAt;
    }

    /** @return Collection<int, Category> */
    public function getCategories(): Collection
    {
        return $this->categories;
    }
}
