<?php

declare(strict_types=1);

namespace App\Feed;

use Dom\Attr;
use Dom\Element;

/** Navigation entre éléments enfants directs, à l'espace de noms près : un `atom:link` n'est pas un `link` RSS. */
final class XmlNodes
{
    public static function child(Element $parent, string $name, ?string $namespace): ?Element
    {
        return self::children($parent, $name, $namespace)->current();
    }

    /** @return \Generator<int, Element> */
    public static function children(Element $parent, string $name, ?string $namespace): \Generator
    {
        for ($child = $parent->firstElementChild; null !== $child; $child = $child->nextElementSibling) {
            if ($name !== $child->localName || $namespace !== $child->namespaceURI) {
                continue;
            }

            yield $child;
        }
    }

    public static function text(Element|Attr|null $node): ?string
    {
        $text = trim((string) $node?->textContent);

        return '' === $text ? null : $text;
    }
}
