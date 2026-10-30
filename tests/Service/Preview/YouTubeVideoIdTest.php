<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Service\Preview\YouTubeVideoId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class YouTubeVideoIdTest extends TestCase
{
    #[DataProvider('videoUrls')]
    public function testReadsIdentifierFromEveryAddressForm(string $url): void
    {
        self::assertSame('qjSAMOkrdIQ', YouTubeVideoId::fromUrl($url));
    }

    /** @return \Generator<string, array{string}> */
    public static function videoUrls(): \Generator
    {
        yield 'watch' => ['https://www.youtube.com/watch?v=qjSAMOkrdIQ&t=42s'];
        yield 'lien court' => ['https://youtu.be/qjSAMOkrdIQ?si=share'];
        yield 'shorts' => ['https://youtube.com/shorts/qjSAMOkrdIQ'];
        yield 'embed' => ['https://www.youtube-nocookie.com/embed/qjSAMOkrdIQ'];
        yield 'live' => ['https://m.youtube.com/live/qjSAMOkrdIQ?feature=share'];
    }

    #[DataProvider('otherUrls')]
    public function testIgnoresOtherAddresses(string $url): void
    {
        self::assertNull(YouTubeVideoId::fromUrl($url));
    }

    /** @return \Generator<string, array{string}> */
    public static function otherUrls(): \Generator
    {
        yield 'chaîne' => ['https://www.youtube.com/@symfonycasts'];
        yield 'identifiant invalide' => ['https://www.youtube.com/watch?v=<script>'];
        yield 'autre domaine' => ['https://example.com/watch?v=qjSAMOkrdIQ'];
    }
}
