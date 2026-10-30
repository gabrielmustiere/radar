<?php

declare(strict_types=1);

namespace App\Service\Preview;

/**
 * Lecture prudente d'une valeur venue d'une source : rien n'est supposé sur son type ni sur sa forme.
 */
final class MetadataValue
{
    private const int URL_MAX_LENGTH = 2048;
    private const string ISO_8601 = '/^\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?(?:Z|[+-]\d{2}:?\d{2})?)?$/';

    /** Texte nettoyé (entités décodées, espaces réduits), `null` si la valeur n'est pas une chaîne non vide. */
    public static function text(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim((string) preg_replace('/\s+/u', replacement: ' ', subject: html_entity_decode($value)));

        return '' === $value ? null : $value;
    }

    public static function int(mixed $value): ?int
    {
        return is_int($value) ? $value : null;
    }

    /**
     * Date ISO 8601 (`2025-03-12`, `2025-03-12T08:30:00+01:00`…), ramenée au fuseau de l'application : la base ne garde
     * pas le décalage horaire. Toute autre forme est refusée, `DateTimeImmutable` lisant sinon `2024` comme « aujourd'hui à 20:24 ».
     */
    public static function date(mixed $value): ?\DateTimeImmutable
    {
        $text = self::text($value);
        if (null === $text || 1 !== preg_match(self::ISO_8601, $text)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($text)->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        } catch (\DateMalformedStringException) {
            return null;
        }
    }

    /** Adresse absolue http(s) de `$href` résolue contre `$baseUrl`, `null` si elle n'en est pas une. */
    public static function url(string $href, string $baseUrl): ?string
    {
        $resolved = \Uri\WhatWg\Url::parse($href, \Uri\WhatWg\Url::parse($baseUrl));
        if (null === $resolved || !in_array($resolved->getScheme(), ['http', 'https'], strict: true)) {
            return null;
        }

        $absolute = $resolved->toAsciiString();

        return strlen($absolute) > self::URL_MAX_LENGTH ? null : $absolute;
    }
}
