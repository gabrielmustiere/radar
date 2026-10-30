<?php

declare(strict_types=1);

namespace App\Service\Preview;

final class HtmlParser
{
    /** `$charset` est celui annoncé par le serveur ; sans lui, le parseur HTML5 lit `<meta charset>` ou détecte. */
    public static function parse(string $html, ?string $charset = null): \Dom\HTMLDocument
    {
        $options = LIBXML_NOERROR | \Dom\HTML_NO_DEFAULT_NS;
        if (null === $charset) {
            return \Dom\HTMLDocument::createFromString($html, $options);
        }

        try {
            return \Dom\HTMLDocument::createFromString($html, $options, $charset);

            // Charset inconnu annoncé par le serveur (`ValueError`) : le parseur détecte l'encodage lui-même.
            // @mago-expect analysis:avoid-catching-error
        } catch (\ValueError) {
            return \Dom\HTMLDocument::createFromString($html, $options);
        }
    }
}
