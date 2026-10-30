<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview\Extractor;

use App\Service\Preview\Extractor\TwitterCardExtractor;
use App\Service\Preview\HtmlPage;
use App\Tests\Service\Preview\PreviewFixture;
use PHPUnit\Framework\TestCase;

final class TwitterCardExtractorTest extends TestCase
{
    public function testReadsTwitterCardTags(): void
    {
        $page = HtmlPage::fromHtml(
            '<meta name="twitter:title" content="Titre"><meta name="twitter:image:src" content="/a.png">',
            'https://example.com/post',
        );

        $preview = new TwitterCardExtractor()->extract('https://example.com/post', $page);

        self::assertSame('Titre', $preview->title);
        self::assertSame('https://example.com/a.png', $preview->imageUrl);
    }

    public function testReadsDescriptionOfArticle(): void
    {
        $page = PreviewFixture::page('article-jsonld-graph.html', 'https://blog.example.com/contrats');

        $preview = new TwitterCardExtractor()->extract('https://blog.example.com/contrats', $page);

        self::assertSame('Ce qu\'un test de contrat garantit, et ce qu\'il ne garantit pas.', $preview->description);
    }
}
