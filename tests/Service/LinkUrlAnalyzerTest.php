<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Enum\Type\SourceType;
use App\Service\LinkUrlAnalyzer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LinkUrlAnalyzerTest extends TestCase
{
    #[DataProvider('equivalentUrls')]
    public function testEquivalentUrlsShareTheSameForm(string $url, string $expected): void
    {
        self::assertSame($expected, new LinkUrlAnalyzer()->normalize($url));
    }

    /** @return \Generator<string, array{string, string}> */
    public static function equivalentUrls(): \Generator
    {
        yield 'http devient https' => ['http://example.com/post', 'https://example.com/post'];
        yield 'domaine en majuscules' => ['https://EXAMPLE.com/Post', 'https://example.com/Post'];
        yield 'préfixe www' => ['https://www.example.com/post', 'https://example.com/post'];
        yield 'barre finale' => ['https://example.com/post/', 'https://example.com/post'];
        yield 'racine' => ['https://example.com/', 'https://example.com'];
        yield 'ancre' => ['https://example.com/post#intro', 'https://example.com/post'];
        yield 'paramètres de suivi' => [
            'https://example.com/post?utm_source=chat&fbclid=x&gclid=y',
            'https://example.com/post',
        ];
        yield 'paramètre significatif conservé' => [
            'https://example.com/list?page=2&utm_medium=x',
            'https://example.com/list?page=2',
        ];
        yield 'vidéo YouTube' => [
            'https://www.youtube.com/watch?v=abc123&si=share',
            'https://youtube.com/watch?v=abc123',
        ];
        yield 'lien court YouTube' => ['https://youtu.be/abc123?si=share', 'https://youtube.com/watch?v=abc123'];
        yield 'port conservé' => ['https://example.com:8443/a', 'https://example.com:8443/a'];
    }

    public function testDistinctParametersStayDistinct(): void
    {
        $normalizer = new LinkUrlAnalyzer();

        self::assertNotSame(
            $normalizer->normalize('https://example.com/list?page=2'),
            $normalizer->normalize('https://example.com/list?page=3'),
        );
    }

    #[DataProvider('urls')]
    public function testResolvesSourceTypeFromUrl(string $url, SourceType $expected): void
    {
        self::assertSame($expected, new LinkUrlAnalyzer()->sourceType($url));
    }

    /** @return \Generator<string, array{string, SourceType}> */
    public static function urls(): \Generator
    {
        yield 'dépôt GitHub' => ['https://github.com/symfony/symfony', SourceType::GitHub];
        yield 'GitHub avec www' => ['https://www.github.com/symfony/ux', SourceType::GitHub];
        yield 'vidéo YouTube' => ['https://www.youtube.com/watch?v=abc', SourceType::YouTube];
        yield 'lien court YouTube' => ['https://youtu.be/abc', SourceType::YouTube];
        yield 'post X' => ['https://x.com/someone/status/1', SourceType::X];
        yield 'ancien domaine Twitter' => ['https://twitter.com/someone', SourceType::X];
        yield 'image' => ['https://example.com/schema.PNG', SourceType::Image];
        yield 'article' => ['https://example.com/blog/post', SourceType::Article];
    }
}
