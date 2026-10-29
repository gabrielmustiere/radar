<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Service\Preview\LinkPreviewParser;
use PHPUnit\Framework\TestCase;

final class LinkPreviewParserTest extends TestCase
{
    public function testReadsOpenGraphAndResolvesRelativeImage(): void
    {
        $html = <<<'HTML'
            <html><head><title>Ignoré</title>
            <meta property="og:title" content="Titre &amp; sous-titre">
            <meta property="og:description" content="  Une   description.  ">
            <meta property="og:image" content="../img/cover.jpg">
            <meta property="og:site_name" content="Le Blog">
            </head></html>
            HTML;

        $preview = new LinkPreviewParser()->parse($html, 'https://example.com/blog/post');

        self::assertSame('Titre & sous-titre', $preview->title);
        self::assertSame('Une description.', $preview->description);
        self::assertSame('https://example.com/img/cover.jpg', $preview->imageUrl);
        self::assertSame('Le Blog', $preview->siteName);
    }

    public function testFallsBackOnTitleTagAndMetaDescription(): void
    {
        $html = '<html><head><title> Mon titre </title><meta name="description" content="Résumé"><meta name="twitter:image" content="https://cdn.example.com/a.png"></head></html>';

        $preview = new LinkPreviewParser()->parse($html, 'https://example.com/');

        self::assertSame('Mon titre', $preview->title);
        self::assertSame('Résumé', $preview->description);
        self::assertSame('https://cdn.example.com/a.png', $preview->imageUrl);
        self::assertNull($preview->siteName);
    }

    public function testIgnoresNonHttpImageAndReturnsEmptyPreviewWithoutMetadata(): void
    {
        $preview = new LinkPreviewParser()->parse(
            '<html><head><meta property="og:image" content="javascript:alert(1)"></head><body>Rien</body></html>',
            'https://example.com/',
        );

        self::assertTrue($preview->isEmpty());
    }

    public function testTruncatesLongTitle(): void
    {
        $preview = new LinkPreviewParser()->parse(
            '<title>' . str_repeat('é', times: 300) . '</title>',
            'https://example.com/',
        );

        self::assertSame(255, mb_strlen((string) $preview->title));
    }
}
