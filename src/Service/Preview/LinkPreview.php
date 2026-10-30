<?php

declare(strict_types=1);

namespace App\Service\Preview;

final readonly class LinkPreview
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $imageUrl = null,
        public ?string $siteName = null,
        public ?string $author = null,
        public ?\DateTimeImmutable $publishedAt = null,
        public ?string $faviconUrl = null,
        public ?int $githubStars = null,
        public ?string $githubLanguage = null,
    ) {}

    public function isEmpty(): bool
    {
        return null === $this->title && null === $this->description && null === $this->imageUrl;
    }

    /** Fusion champ par champ : chaque valeur de cet aperçu prime, `$fallback` ne comble que ses manques. */
    public function withFallback(self $fallback): self
    {
        return new self(
            $this->title ?? $fallback->title,
            $this->description ?? $fallback->description,
            $this->imageUrl ?? $fallback->imageUrl,
            $this->siteName ?? $fallback->siteName,
            $this->author ?? $fallback->author,
            $this->publishedAt ?? $fallback->publishedAt,
            $this->faviconUrl ?? $fallback->faviconUrl,
            $this->githubStars ?? $fallback->githubStars,
            $this->githubLanguage ?? $fallback->githubLanguage,
        );
    }

    public function withText(?string $title, ?string $description, ?string $siteName, ?string $author): self
    {
        return new self(
            $title,
            $description,
            $this->imageUrl,
            $siteName,
            $author,
            $this->publishedAt,
            $this->faviconUrl,
            $this->githubStars,
            $this->githubLanguage,
        );
    }
}
