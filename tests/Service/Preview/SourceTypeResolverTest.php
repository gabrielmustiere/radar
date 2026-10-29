<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Enum\Type\SourceType;
use App\Service\Preview\SourceTypeResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SourceTypeResolverTest extends TestCase
{
    #[DataProvider('urls')]
    public function testResolvesSourceTypeFromUrl(string $url, SourceType $expected): void
    {
        self::assertSame($expected, new SourceTypeResolver()->resolve($url));
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
