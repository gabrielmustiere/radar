<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview\Extractor;

use App\Service\Preview\Extractor\OpenGraphExtractor;
use App\Tests\Service\Preview\PreviewFixture;
use PHPUnit\Framework\TestCase;

final class OpenGraphExtractorTest extends TestCase
{
    public function testReadsOpenGraphAndResolvesRelativeImageAgainstBaseHref(): void
    {
        $page = PreviewFixture::page('og-relative.html', 'https://example.com/blog/post');

        $preview = new OpenGraphExtractor()->extract('https://example.com/blog/post', $page);

        self::assertSame('Titre & sous-titre', $preview->title);
        self::assertSame('Une description.', $preview->description);
        self::assertSame('https://cdn.example.com/img/cover.jpg', $preview->imageUrl);
        self::assertSame('Le Blog', $preview->siteName);
        self::assertSame('2026-01-15', $preview->publishedAt?->format('Y-m-d'));
    }

    public function testReadsGitHubRepositoryPage(): void
    {
        $page = PreviewFixture::page('github-repo.html', 'https://github.com/symfony/symfony');

        $preview = new OpenGraphExtractor()->extract('https://github.com/symfony/symfony', $page);

        self::assertSame('GitHub - symfony/symfony: The Symfony PHP framework', $preview->title);
        self::assertSame('GitHub', $preview->siteName);
        self::assertStringStartsWith('https://repository-images.githubusercontent.com/', (string) $preview->imageUrl);
    }

    public function testIgnoresNonHttpImage(): void
    {
        $page = \App\Service\Preview\HtmlPage::fromHtml(
            '<meta property="og:image" content="javascript:alert(1)">',
            'https://example.com/',
        );

        self::assertTrue(
            new OpenGraphExtractor()
                ->extract('https://example.com/', $page)
                ->isEmpty(),
        );
    }

    public function testExtractsNothingWithoutPage(): void
    {
        self::assertTrue(
            new OpenGraphExtractor()
                ->extract('https://example.com/', null)
                ->isEmpty(),
        );
    }
}
