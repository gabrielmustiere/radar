<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview\Extractor;

use App\Service\Preview\Extractor\JsonLdExtractor;
use App\Service\Preview\HtmlPage;
use App\Tests\Service\Preview\PreviewFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class JsonLdExtractorTest extends TestCase
{
    public function testReadsArticleFromGraphAcrossSeveralBlocks(): void
    {
        $page = PreviewFixture::page('article-jsonld-graph.html', 'https://blog.example.com/2025/contrats');

        $preview = new JsonLdExtractor()->extract('https://blog.example.com/2025/contrats', $page);

        self::assertSame('Les tests de contrat et leurs limites', $preview->title);
        self::assertSame('Camille Martin, Léa Dubois', $preview->author);
        self::assertSame('2025-03-12', $preview->publishedAt?->format('Y-m-d'));
        self::assertSame('https://blog.example.com/uploads/contrat.png', $preview->imageUrl);
    }

    #[DataProvider('authors')]
    public function testReadsAuthorInEveryShape(string $author, string $expected): void
    {
        $page = HtmlPage::fromHtml(
            sprintf('<script type="application/ld+json">{"@type": "NewsArticle", "author": %s}</script>', $author),
            'https://example.com/',
        );

        self::assertSame($expected, new JsonLdExtractor()->extract('https://example.com/', $page)->author);
    }

    /** @return \Generator<string, array{string, string}> */
    public static function authors(): \Generator
    {
        yield 'chaîne' => ['"Camille Martin"', 'Camille Martin'];
        yield 'objet' => ['{"@type": "Person", "name": "Camille Martin"}', 'Camille Martin'];
        yield 'tableau' => ['["Camille Martin", {"name": "Léa Dubois"}]', 'Camille Martin, Léa Dubois'];
    }

    public function testIgnoresInvalidJsonAndNonContentNodes(): void
    {
        $page = HtmlPage::fromHtml(
            '<script type="application/ld+json">{invalide</script>'
            . '<script type="application/ld+json">{"@type": "Organization", "name": "ACME"}</script>',
            'https://example.com/',
        );

        self::assertTrue(
            new JsonLdExtractor()
                ->extract('https://example.com/', $page)
                ->isEmpty(),
        );
    }

    public function testIgnoresUnreadablePublicationDate(): void
    {
        $page = HtmlPage::fromHtml(
            '<script type="application/ld+json">{"@type": "Article", "headline": "Titre", "datePublished": "hier"}</script>',
            'https://example.com/',
        );

        self::assertNull(new JsonLdExtractor()->extract('https://example.com/', $page)->publishedAt);
    }
}
