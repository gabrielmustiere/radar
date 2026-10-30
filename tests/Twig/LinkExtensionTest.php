<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\LinkExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LinkExtensionTest extends TestCase
{
    public function testGitHubRepositoryNamesOwnerAndRepository(): void
    {
        $extension = new LinkExtension();

        self::assertSame('symfony/ux', $extension->gitHubRepository('https://github.com/symfony/ux/issues/42'));
        self::assertNull($extension->gitHubRepository('https://github.com/symfony'));
    }

    #[DataProvider('xUrls')]
    public function testXHandle(string $url, ?string $expected): void
    {
        self::assertSame($expected, new LinkExtension()->xHandle($url));
    }

    /** @return \Generator<string, array{string, ?string}> */
    public static function xUrls(): \Generator
    {
        yield 'post' => ['https://x.com/karpathy/status/2079610838143623371', 'karpathy'];
        yield 'profil' => ['https://twitter.com/symfony', 'symfony'];
        yield 'post sans compte' => ['https://x.com/i/status/2079610838143623371', null];
        yield 'accueil' => ['https://x.com/', null];
    }
}
