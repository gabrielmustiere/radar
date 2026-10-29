<?php

declare(strict_types=1);

namespace App\Tests\Search;

use App\Search\SearchQuery;
use PHPUnit\Framework\TestCase;

final class SearchQueryTest extends TestCase
{
    public function testSplitsOnWhitespaceAndIgnoresShortWords(): void
    {
        self::assertSame(['tester', 'mobile'], SearchQuery::fromString("  tester  a\tmobile ")->words);
    }

    public function testDropsCaseInsensitiveDuplicatesAndKeepsFiveWords(): void
    {
        $query = SearchQuery::fromString('Php php un deux trois quatre cinq six');

        self::assertSame(['Php', 'un', 'deux', 'trois', 'quatre'], $query->words);
    }

    public function testBlankInputIsEmpty(): void
    {
        self::assertTrue(SearchQuery::fromString('   ')->isEmpty());
        self::assertTrue(SearchQuery::fromString('a')->isEmpty());
    }
}
