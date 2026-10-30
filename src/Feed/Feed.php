<?php

declare(strict_types=1);

namespace App\Feed;

final readonly class Feed
{
    /** @param list<FeedItem> $items dans l'ordre du document, le plus récent en tête par convention des flux */
    public function __construct(
        public string $title,
        public array $items,
    ) {}

    /**
     * Les articles écartés (`null`) disparaissent ; une clé répétée ne compte qu'une fois, à sa première occurrence.
     *
     * @param list<?FeedItem> $items
     */
    public static function fromItems(string $title, array $items): self
    {
        $unique = [];
        foreach ($items as $item) {
            if (null === $item || array_key_exists($item->key, $unique)) {
                continue;
            }

            $unique[$item->key] = $item;
        }

        return new self($title, array_values($unique));
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_map(static fn(FeedItem $item): string => $item->key, $this->items);
    }
}
