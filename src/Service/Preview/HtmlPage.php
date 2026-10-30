<?php

declare(strict_types=1);

namespace App\Service\Preview;

/**
 * Page HTML téléchargée : balises `<meta>`, `<title>`, `<link>` et blocs JSON-LD, valeurs nettoyées,
 * adresses résolues contre `<base href>` ou l'URL finale.
 */
final readonly class HtmlPage
{
    private function __construct(
        public string $url,
        private string $baseUrl,
        private \Dom\HTMLDocument $document,
    ) {}

    public static function fromHtml(string $html, string $url, ?string $charset = null): self
    {
        $document = HtmlParser::parse($html, $charset);
        $base = $document->querySelector('base[href]')?->getAttribute('href');

        return new self($url, MetadataValue::url($base ?? $url, $url) ?? $url, $document);
    }

    /** Valeur de la première balise `<meta>` présente parmi `$names` (attribut `property` ou `name`). */
    public function meta(string ...$names): ?string
    {
        $metas = $this->metas();
        foreach ($names as $name) {
            if (array_key_exists($name, $metas)) {
                return $metas[$name];
            }
        }

        return null;
    }

    public function title(): ?string
    {
        return MetadataValue::text($this->document->querySelector('title')?->textContent);
    }

    /** Adresse absolue http(s), résolue contre `<base href>` ou l'URL finale. */
    public function absoluteUrl(?string $href): ?string
    {
        return null === $href ? null : MetadataValue::url($href, $this->baseUrl);
    }

    /** @return list<array{type: string, href: string}> balises `<link>` portant `$rel`, adresse absolue */
    public function links(string $rel): array
    {
        $links = [];
        foreach ($this->elements('link[rel][href]') as $link) {
            $rels = explode(' ', strtolower((string) $link->getAttribute('rel')));
            $href = $this->absoluteUrl(trim((string) $link->getAttribute('href')));
            if (null !== $href && in_array($rel, $rels, strict: true)) {
                $links[] = ['type' => strtolower(trim((string) $link->getAttribute('type'))), 'href' => $href];
            }
        }

        return $links;
    }

    /** @return list<string> contenu brut des blocs `<script type="application/ld+json">` */
    public function jsonLdBlocks(): array
    {
        return array_map(
            static fn(\Dom\Element $script): string => (string) $script->textContent,
            $this->elements('script[type="application/ld+json"]'),
        );
    }

    /** @return array<string, string> première valeur non vide de chaque balise, clé en minuscules */
    private function metas(): array
    {
        $metas = [];
        foreach ($this->elements('meta[content]') as $meta) {
            $key = strtolower($meta->getAttribute('property') ?? $meta->getAttribute('name') ?? '');
            $value = MetadataValue::text($meta->getAttribute('content'));
            if (null !== $value && !array_key_exists($key, $metas)) {
                $metas[$key] = $value;
            }
        }

        return $metas;
    }

    /** @return list<\Dom\Element> */
    private function elements(string $selector): array
    {
        $elements = [];
        foreach ($this->document->querySelectorAll($selector) as $node) {
            if (!$node instanceof \Dom\Element) {
                continue;
            }

            $elements[] = $node;
        }

        return $elements;
    }
}
