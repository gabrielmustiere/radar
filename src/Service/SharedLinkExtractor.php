<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Lien reçu par le menu Partager du téléphone. Beaucoup d'apps Android ne remplissent pas `url` et glissent le lien
 * dans `text` (« Titre de la vidéo https://… ») : on retient le premier lien trouvé, dans cet ordre.
 */
final class SharedLinkExtractor
{
    public function extract(?string $url, ?string $text, ?string $title): ?string
    {
        foreach ([$url, $text, $title] as $candidate) {
            $matches = [];
            if (1 !== preg_match('#https?://[^\s<>"]+#i', (string) $candidate, $matches)) {
                continue;
            }

            $link = $this->withoutTrailingPunctuation($matches[0] ?? '');
            if (null !== \Uri\WhatWg\Url::parse($link)) {
                return $link;
            }
        }

        return null;
    }

    /** Une parenthèse finale n'est gardée que si le lien l'ouvre lui-même (`…/wiki/Radar_(homonymie)`). */
    private function withoutTrailingPunctuation(string $link): string
    {
        $link = rtrim($link, characters: '.,;:!?»"\'');
        $unbalanced = substr_count($link, needle: '(') < substr_count($link, needle: ')');

        return str_ends_with($link, ')') && $unbalanced ? substr($link, offset: 0, length: -1) : $link;
    }
}
