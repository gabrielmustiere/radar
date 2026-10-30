<?php

declare(strict_types=1);

namespace App\Feed;

use App\Exception\InvalidFeedException;
use Dom\Element;
use Dom\XMLDocument;

/**
 * RSS 2.0 et Atom 1.0 : titre du flux, clé et lien de chaque article. Les entités externes ne sont jamais résolues.
 */
final class FeedParser
{
    private const string ATOM_NS = 'http://www.w3.org/2005/Atom';

    /** @throws InvalidFeedException */
    public function parse(string $xml): Feed
    {
        if ('' === trim($xml)) {
            throw new InvalidFeedException('Le flux est vide.');
        }

        try {
            $root = XMLDocument::createFromString(
                $xml,
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING,
            )->documentElement;
        } catch (\DOMException) {
            throw new InvalidFeedException('Le flux est illisible : ce n\'est pas du XML valide.');
        }

        return match (true) {
            'rss' === $root?->localName && null === $root->namespaceURI => $this->rss($root),
            'feed' === $root?->localName && self::ATOM_NS === $root->namespaceURI => $this->atom($root),
            default => throw new InvalidFeedException('Cette adresse ne mène pas à un flux RSS ou Atom.'),
        };
    }

    private function rss(Element $root): Feed
    {
        $channel = XmlNodes::child($root, 'channel', null) ?? throw new InvalidFeedException(
            'Le flux RSS n\'a pas de canal.',
        );

        $items = [];
        foreach (XmlNodes::children($channel, 'item', null) as $item) {
            $items[] = FeedItem::tryFrom(
                XmlNodes::text(XmlNodes::child($item, 'guid', null)),
                XmlNodes::text(XmlNodes::child($item, 'link', null)),
                XmlNodes::text(XmlNodes::child($item, 'pubDate', null)),
            );
        }

        return Feed::fromItems(XmlNodes::text(XmlNodes::child($channel, 'title', null)) ?? '', $items);
    }

    private function atom(Element $root): Feed
    {
        $items = [];
        foreach (XmlNodes::children($root, 'entry', self::ATOM_NS) as $entry) {
            $items[] = FeedItem::tryFrom(
                XmlNodes::text(XmlNodes::child($entry, 'id', self::ATOM_NS)),
                $this->alternateLink($entry),
                XmlNodes::text(
                    XmlNodes::child($entry, 'published', self::ATOM_NS) ?? XmlNodes::child(
                        $entry,
                        'updated',
                        self::ATOM_NS,
                    ),
                ),
            );
        }

        return Feed::fromItems(XmlNodes::text(XmlNodes::child($root, 'title', self::ATOM_NS)) ?? '', $items);
    }

    private function alternateLink(Element $entry): ?string
    {
        foreach (XmlNodes::children($entry, 'link', self::ATOM_NS) as $link) {
            if (in_array($link->getAttribute('rel') ?? 'alternate', ['', 'alternate'], strict: true)) {
                return XmlNodes::text($link->getAttributeNode('href'));
            }
        }

        return null;
    }
}
