<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Service\Preview\TextTruncator;
use PHPUnit\Framework\TestCase;

final class TextTruncatorTest extends TestCase
{
    public function testKeepsShortText(): void
    {
        self::assertSame('Court', new TextTruncator()->truncate('Court', 10));
        self::assertNull(new TextTruncator()->truncate(null, 10));
    }

    public function testCutsOnLastWholeWord(): void
    {
        self::assertSame('Les tests de…', new TextTruncator()->truncate('Les tests de contrat', 15));
    }

    public function testDropsTrailingPunctuationBeforeEllipsis(): void
    {
        self::assertSame('Un titre…', new TextTruncator()->truncate('Un titre, avec une suite', 12));
    }

    public function testNeverBreaksMultibyteCharacters(): void
    {
        $truncated = (string) new TextTruncator()->truncate(str_repeat('À', times: 30), 10);

        self::assertSame(str_repeat('À', times: 9) . '…', $truncated);
        self::assertTrue(mb_check_encoding($truncated, encoding: 'UTF-8'));
    }
}
