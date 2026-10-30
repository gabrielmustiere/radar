<?php

declare(strict_types=1);

namespace App\Service\Preview;

/**
 * Auteur schema.org : chaîne, objet `Person`, référence `@id` vers un nœud du graphe, ou liste de tout cela.
 */
final class JsonLdAuthor
{
    public static function names(mixed $author, JsonLdGraph $graph): ?string
    {
        $items = is_array($author) && array_is_list($author) ? $author : [$author];
        $names = array_filter(array_map(static fn(mixed $item): ?string => self::name($item, $graph), $items));

        return [] === $names ? null : implode(', ', array_unique($names));
    }

    private static function name(mixed $item, JsonLdGraph $graph): ?string
    {
        if (!is_array($item)) {
            return MetadataValue::text($item);
        }

        $id = MetadataValue::text($item['@id'] ?? null);
        if (array_key_exists('name', $item) || null === $id) {
            return MetadataValue::text($item['name'] ?? null);
        }

        return MetadataValue::text($graph->node($id)['name'] ?? null);
    }
}
