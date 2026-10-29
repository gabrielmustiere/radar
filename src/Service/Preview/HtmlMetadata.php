<?php

declare(strict_types=1);

namespace App\Service\Preview;

/**
 * Balises `<meta>` et `<title>` d'une page HTML, nettoyées (entités décodées, espaces réduits).
 */
final readonly class HtmlMetadata
{
    /** @param array<string, string> $metas première valeur non vide de chaque balise, clé en minuscules */
    private function __construct(
        private array $metas,
        private ?string $title,
    ) {}

    public static function fromHtml(string $html): self
    {
        $document = \Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR | \Dom\HTML_NO_DEFAULT_NS);

        $metas = [];
        foreach ($document->querySelectorAll('meta[content]') as $meta) {
            if (!$meta instanceof \Dom\Element) {
                continue;
            }

            $key = strtolower($meta->getAttribute('property') ?? $meta->getAttribute('name') ?? '');
            $value = self::clean($meta->getAttribute('content'));
            if ('' !== $key && null !== $value && !array_key_exists($key, $metas)) {
                $metas[$key] = $value;
            }
        }

        return new self($metas, self::clean($document->querySelector('title')?->textContent));
    }

    /** Valeur de la première balise présente parmi `$names`. */
    public function first(string ...$names): ?string
    {
        foreach ($names as $name) {
            if (array_key_exists($name, $this->metas)) {
                return $this->metas[$name];
            }
        }

        return null;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) preg_replace('/\s+/u', replacement: ' ', subject: html_entity_decode((string) $value)));

        return '' === $value ? null : $value;
    }
}
