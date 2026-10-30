<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview\Extractor;

use App\Service\Preview\Extractor\HtmlExtractor;
use App\Tests\Service\Preview\PreviewFixture;
use PHPUnit\Framework\TestCase;

final class HtmlExtractorTest extends TestCase
{
    public function testFallsBackOnTitleTag(): void
    {
        $page = PreviewFixture::page('title-only.html', 'https://example.com/page');

        $preview = new HtmlExtractor()->extract('https://example.com/page', $page);

        self::assertSame('Une page sans aperçu', $preview->title);
        self::assertNull($preview->description);
        self::assertNull($preview->faviconUrl);
    }

    public function testReadsMetaDescription(): void
    {
        $page = PreviewFixture::page('article-jsonld-graph.html', 'https://blog.example.com/contrats');

        $preview = new HtmlExtractor()->extract('https://blog.example.com/contrats', $page);

        self::assertSame('Les tests de contrat & leurs limites | Le Blog Tech', $preview->title);
        self::assertSame('Description de repli.', $preview->description);
    }

    public function testPicksFirstRasterFavicon(): void
    {
        $page = PreviewFixture::page('github-repo.html', 'https://github.com/symfony/symfony');

        $preview = new HtmlExtractor()->extract('https://github.com/symfony/symfony', $page);

        self::assertSame('https://github.githubassets.com/favicons/favicon.png', $preview->faviconUrl);
    }

    public function testSkipsIcoFaviconAndResolvesAgainstBaseHref(): void
    {
        $page = PreviewFixture::page('og-relative.html', 'https://example.com/blog/post');

        $preview = new HtmlExtractor()->extract('https://example.com/blog/post', $page);

        self::assertSame('https://cdn.example.com/touch-icon.png', $preview->faviconUrl);
    }

    public function testDecodesLatinOneCharsetAnnouncedByServer(): void
    {
        $page = PreviewFixture::page('latin1.html', 'https://example.com/', 'ISO-8859-1');

        self::assertSame('Café crème', new HtmlExtractor()->extract('https://example.com/', $page)->title);
    }

    public function testIgnoresUnknownCharsetAnnouncedByServer(): void
    {
        $page = PreviewFixture::page('title-only.html', 'https://example.com/', 'pas-un-charset');

        self::assertSame('Une page sans aperçu', new HtmlExtractor()->extract('https://example.com/', $page)->title);
    }
}
