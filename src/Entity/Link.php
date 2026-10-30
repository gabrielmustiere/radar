<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Type\PreviewStatus;
use App\Enum\Type\SourceType;
use App\Repository\LinkRepository;
use App\Service\Preview\LinkPreview;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LinkRepository::class)]
#[ORM\Index(name: 'IDX_LINK_POSTED_AT', fields: ['postedAt'])]
#[ORM\Index(name: 'IDX_LINK_PREVIEW_FETCHED_AT', fields: ['previewFetchedAt'])]
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(name: 'published_at', nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(name: 'image_path', length: 100, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(name: 'favicon_path', length: 100, nullable: true)]
    private ?string $faviconPath = null;

    #[ORM\Column(name: 'github_stars', nullable: true)]
    private ?int $githubStars = null;

    #[ORM\Column(name: 'github_language', length: 50, nullable: true)]
    private ?string $githubLanguage = null;

    #[ORM\Column(name: 'preview_fetched_at', nullable: true)]
    private ?\DateTimeImmutable $previewFetchedAt = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $seeded;

    #[ORM\Column(name: 'announced_at', nullable: true)]
    private ?\DateTimeImmutable $announcedAt = null;

    /** @var Collection<int, Category> */
    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: 'link_category')]
    #[ORM\OrderBy(['name' => 'ASC'])]
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
        bool $seeded = false,
    ) {
        $this->seeded = $seeded;
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

    public function isSeeded(): bool
    {
        return $this->seeded;
    }

    public function getAnnouncedAt(): ?\DateTimeImmutable
    {
        return $this->announcedAt;
    }

    public function markAnnounced(\DateTimeImmutable $announcedAt): void
    {
        $this->announcedAt = $announcedAt;
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

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function getFaviconPath(): ?string
    {
        return $this->faviconPath;
    }

    public function getGithubStars(): ?int
    {
        return $this->githubStars;
    }

    public function getGithubLanguage(): ?string
    {
        return $this->githubLanguage;
    }

    public function getPreviewFetchedAt(): ?\DateTimeImmutable
    {
        return $this->previewFetchedAt;
    }

    /** Aperçu actuellement enregistré (champs vides tant qu'aucun aperçu n'a abouti). */
    public function previewSnapshot(): LinkPreview
    {
        return new LinkPreview(
            $this->title,
            $this->description,
            $this->imageUrl,
            $this->siteName,
            $this->author,
            $this->publishedAt,
            null,
            $this->githubStars,
            $this->githubLanguage,
        );
    }

    /** `$imagePath` et `$faviconPath` désignent les visuels conservés par Radar ; `imageUrl` reste l'image d'origine. */
    public function applyPreview(
        LinkPreview $preview,
        ?string $imagePath,
        ?string $faviconPath,
        \DateTimeImmutable $fetchedAt,
    ): void {
        $this->title = $preview->title;
        $this->description = $preview->description;
        $this->imageUrl = $preview->imageUrl;
        $this->siteName = $preview->siteName;
        $this->author = $preview->author;
        $this->publishedAt = $preview->publishedAt;
        $this->githubStars = $preview->githubStars;
        $this->githubLanguage = $preview->githubLanguage;
        $this->imagePath = $imagePath;
        $this->faviconPath = $faviconPath;
        $this->previewStatus = PreviewStatus::Ready;
        $this->previewFetchedAt = $fetchedAt;
    }

    /** Un échec conserve le dernier aperçu connu : seule la date de la tentative change. */
    public function markPreviewUnavailable(\DateTimeImmutable $fetchedAt): void
    {
        if (PreviewStatus::Ready !== $this->previewStatus) {
            $this->previewStatus = PreviewStatus::Unavailable;
        }

        $this->previewFetchedAt = $fetchedAt;
    }
}
