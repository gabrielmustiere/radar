<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Type\PreviewStatus;
use App\Enum\Type\SourceType;
use App\Repository\LinkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LinkRepository::class)]
#[ORM\Index(name: 'IDX_LINK_POSTED_AT', fields: ['postedAt'])]
#[ORM\UniqueConstraint(name: 'UNIQ_LINK_NORMALIZED_URL', fields: ['normalizedUrl'])]
class Link
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 2048)]
    private string $url;

    // Le défaut n'existe que parce que SQLite refuse ADD COLUMN NOT NULL sans défaut : la valeur est toujours posée au constructeur.
    #[ORM\Column(name: 'normalized_url', length: 2048, options: ['default' => ''])]
    private string $normalizedUrl;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $context;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'posted_by_id', nullable: false, onDelete: 'RESTRICT')]
    private User $postedBy;

    #[ORM\Column(name: 'posted_at')]
    private \DateTimeImmutable $postedAt;

    #[ORM\Column(name: 'source_type', length: 20, enumType: SourceType::class, options: ['default' => 'article'])]
    private SourceType $sourceType;

    #[ORM\Column(name: 'preview_status', length: 20, enumType: PreviewStatus::class, options: ['default' => 'pending'])]
    private PreviewStatus $previewStatus = PreviewStatus::Pending;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'image_url', length: 2048, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'site_name', length: 255, nullable: true)]
    private ?string $siteName = null;

    /** @var Collection<int, Category> */
    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: 'link_category')]
    private Collection $categories;

    /** @param iterable<Category> $categories */
    public function __construct(
        string $url,
        string $normalizedUrl,
        iterable $categories,
        User $postedBy,
        \DateTimeImmutable $postedAt,
        SourceType $sourceType,
        ?string $context = null,
    ) {
        $this->url = $url;
        $this->normalizedUrl = $normalizedUrl;
        $this->sourceType = $sourceType;
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

    public function getNormalizedUrl(): string
    {
        return $this->normalizedUrl;
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

    public function getSourceType(): SourceType
    {
        return $this->sourceType;
    }

    public function getPreviewStatus(): PreviewStatus
    {
        return $this->previewStatus;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getSiteName(): ?string
    {
        return $this->siteName;
    }

    public function applyPreview(?string $title, ?string $description, ?string $imageUrl, ?string $siteName): void
    {
        $this->title = $title;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
        $this->siteName = $siteName;
        $this->previewStatus = PreviewStatus::Ready;
    }

    public function markPreviewUnavailable(): void
    {
        $this->previewStatus = PreviewStatus::Unavailable;
    }
}
