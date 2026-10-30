<?php

declare(strict_types=1);

namespace App\Service\Preview;

/**
 * Nœuds schema.org d'une page, tous blocs confondus, `@graph` déplié.
 */
final readonly class JsonLdGraph
{
    /** @param list<array<array-key, mixed>> $nodes */
    private function __construct(
        private array $nodes,
    ) {}

    /** @param list<string> $blocks */
    public static function fromBlocks(array $blocks): self
    {
        $nodes = [];
        foreach ($blocks as $block) {
            foreach (self::arrays(json_decode($block, associative: true)) as $node) {
                $graph = self::arrays($node['@graph'] ?? null);
                $nodes = [...$nodes, ...([] === $graph ? [$node] : $graph)];
            }
        }

        return new self($nodes);
    }

    /**
     * Premier nœud du premier type trouvé dans `$types`, pris dans l'ordre.
     *
     * @param list<string> $types
     *
     * @return array<array-key, mixed>|null
     */
    public function firstOfType(array $types): ?array
    {
        foreach ($types as $type) {
            foreach ($this->nodes as $node) {
                if (in_array($type, self::types($node['@type'] ?? null), strict: true)) {
                    return $node;
                }
            }
        }

        return null;
    }

    /** @return array<array-key, mixed>|null */
    public function node(string $id): ?array
    {
        foreach ($this->nodes as $node) {
            if ($id === ($node['@id'] ?? null)) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Objets JSON d'une valeur : l'objet lui-même, ou ceux d'une liste.
     *
     * @return list<array<array-key, mixed>>
     */
    private static function arrays(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_is_list($value) ? $value : [$value], is_array(...)));
    }

    /** @return list<string> */
    private static function types(mixed $type): array
    {
        return array_values(array_filter(is_array($type) ? $type : [$type], is_string(...)));
    }
}
