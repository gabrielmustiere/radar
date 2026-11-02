<?php

declare(strict_types=1);

namespace App\Tests\Api;

final class ExtensionDuplicateTest extends ExtensionApiTestCase
{
    public function testAddressNotInRadarHasNoDuplicate(): void
    {
        $this->api(
            'GET',
            '/api/extension/duplicate?url=' . rawurlencode('https://example.com/' . uniqid('absent-')),
            $this->keyFor($this->member()),
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testDuplicateIsDescribedWithItsFreeSlots(): void
    {
        $key = $this->keyFor($this->member());
        $url = $this->existingLink($key, ['Agents']);

        $body = $this->api('GET', '/api/extension/duplicate?url=' . rawurlencode($url . '/'), $key);

        self::assertResponseIsSuccessful();
        self::assertSame(['Agents'], self::categoryNames($body));
        self::assertSame(2, $body['freeSlots'] ?? null);
        self::assertIsInt($body['id'] ?? null);
        self::assertSame('http://localhost/liens/' . $body['id'], $body['cardUrl'] ?? null);
    }

    public function testDuplicateIsCompletedWithoutAnnouncement(): void
    {
        $key = $this->keyFor($this->member());
        $url = $this->existingLink($key, ['Agents']);
        $duplicate = $this->api('GET', '/api/extension/duplicate?url=' . rawurlencode($url), $key);
        self::assertIsInt($duplicate['id'] ?? null);

        $body = $this->api('POST', sprintf('/api/extension/duplicate/%d/categories', $duplicate['id']), $key, [
            'categories' => [$this->categoryId('Agents'), $this->categoryId('IA')],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame(['Agents', 'IA'], self::categoryNames($body));
        self::assertSame(1, $body['freeSlots'] ?? null);
        self::assertNotificationCount(0);
    }

    public function testCompletionBeyondThreeCategoriesIsRefused(): void
    {
        $key = $this->keyFor($this->member());
        $url = $this->existingLink($key, ['Agents', 'IA']);
        $duplicate = $this->api('GET', '/api/extension/duplicate?url=' . rawurlencode($url), $key);
        self::assertIsInt($duplicate['id'] ?? null);

        $this->api('POST', sprintf('/api/extension/duplicate/%d/categories', $duplicate['id']), $key, [
            'categories' => [$this->categoryId('Architecture'), $this->categoryId('Symfony')],
        ]);

        self::assertResponseStatusCodeSame(409);
    }

    /** @param list<string> $categories */
    private function existingLink(string $key, array $categories): string
    {
        $url = 'https://example.com/' . uniqid('existant-');
        $this->api('POST', '/api/extension/links', $key, [
            'url' => $url,
            'categories' => array_map($this->categoryId(...), $categories),
        ]);
        self::assertResponseStatusCodeSame(201);

        return $url;
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return list<string>
     */
    private static function categoryNames(array $body): array
    {
        self::assertIsArray($body['categories'] ?? null);
        /** @var list<array{name: string}> $categories */
        $categories = $body['categories'];
        $names = array_column($categories, 'name');
        sort($names);

        return $names;
    }
}
