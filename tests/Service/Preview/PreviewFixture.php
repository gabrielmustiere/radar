<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Service\Preview\HtmlPage;

/**
 * Pages et réponses statiques des sources (`tests/Fixtures/preview/`) : aucun appel réseau.
 */
final class PreviewFixture
{
    public static function content(string $file): string
    {
        return (string) file_get_contents(dirname(__DIR__, levels: 2) . '/Fixtures/preview/' . $file);
    }

    public static function page(string $file, string $url, ?string $charset = null): HtmlPage
    {
        return HtmlPage::fromHtml(self::content($file), $url, $charset);
    }
}
