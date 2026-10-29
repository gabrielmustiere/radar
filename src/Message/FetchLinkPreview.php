<?php

declare(strict_types=1);

namespace App\Message;

final readonly class FetchLinkPreview
{
    public function __construct(
        public int $linkId,
    ) {}
}
