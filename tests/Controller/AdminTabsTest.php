<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;

final class AdminTabsTest extends LinkWebTestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function pages(): iterable
    {
        yield 'sources' => ['/sources', 'Sources'];
        yield 'comptes' => ['/admin/comptes', 'Comptes'];
        yield 'nouveau compte' => ['/admin/comptes/nouveau', 'Comptes'];
        yield 'catégories' => ['/admin/categories', 'Catégories'];
    }

    #[DataProvider('pages')]
    public function testAdminPageShowsTabsWithItsOwnActive(string $path, string $active): void
    {
        $client = $this->loggedClient();

        $client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(3, '[data-test="admin-tabs"] [data-test="admin-tab"]');
        self::assertSelectorTextSame('[data-test="admin-tab"][aria-current="page"]', $active);
    }
}
