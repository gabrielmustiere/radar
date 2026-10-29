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
    ) {}

    public function isEmpty(): bool
    {
        return null === $this->title && null === $this->description && null === $this->imageUrl;
    }
}
