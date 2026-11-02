<?php

declare(strict_types=1);

namespace App\Tests\Api;

final class ExtensionCategoryTest extends ExtensionApiTestCase
{
    public function testCategoriesAreListedByName(): void
    {
        $names = self::memberNames($this->api('GET', '/api/extension/categories', $this->keyFor($this->member())));

        $sorted = $names;
        sort($sorted);
        self::assertSame($sorted, $names);
    }

    public function testNewCategoryIsCreatedWithAnInitialCapital(): void
    {
        $name = 'tester une app ' . uniqid();

        $body = $this->api('POST', '/api/extension/categories', $this->keyFor($this->member()), [
            'name' => '  ' . $name,
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(ucfirst($name), $body['name'] ?? null);
        self::assertIsInt($body['id'] ?? null);
    }

    public function testExistingCategoryIsReusedWhateverTheCase(): void
    {
        $key = $this->keyFor($this->member());
        $agents = $this->api('POST', '/api/extension/categories', $key, ['name' => 'Agents']);

        $body = $this->api('POST', '/api/extension/categories', $key, ['name' => 'aGENTS']);

        self::assertSame('Agents', $body['name'] ?? null);
        self::assertSame($agents['id'] ?? null, $body['id'] ?? null);
    }

    public function testTooShortOrTooLongNameIsRejected(): void
    {
        $key = $this->keyFor($this->member());

        $body = $this->api('POST', '/api/extension/categories', $key, ['name' => ' x ']);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('au moins 2 caractères', self::violations($body));

        $body = $this->api('POST', '/api/extension/categories', $key, ['name' => str_repeat('a', times: 61)]);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('au plus 60 caractères', self::violations($body));
    }
}
