<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Repository\LinkRepository;

final class ExtensionLinkTest extends ExtensionApiTestCase
{
    public function testLinkIsAddedForTheKeyOwnerAndAnnounced(): void
    {
        $member = $this->member();
        $url = 'https://example.com/article-' . uniqid('extension-');

        $body = $this->api('POST', '/api/extension/links', $this->keyFor($member), [
            'url' => $url,
            'context' => 'À lire.',
            'categories' => [$this->categoryId('Agents'), $this->categoryId('IA')],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertIsInt($body['id'] ?? null);
        self::assertSame('http://localhost/liens/' . $body['id'], $body['cardUrl'] ?? null);
        $link = $this->links()->find($body['id']);
        self::assertNotNull($link);
        self::assertSame($member->getEmail(), $link->getPostedBy()->getEmail());
        self::assertSame('À lire.', $link->getContext());
        self::assertCount(2, $link->getCategories());
        self::assertNotificationCount(1);
    }

    public function testCategoryCountFollowsTheFormRules(): void
    {
        $key = $this->keyFor($this->member());
        $url = 'https://example.com/' . uniqid('plafond-');

        $body = $this->api('POST', '/api/extension/links', $key, ['url' => $url, 'categories' => []]);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Choisissez au moins une catégorie', self::violations($body));

        $four = array_map($this->categoryId(...), ['Agents', 'Architecture', 'IA', 'Symfony']);
        $body = $this->api('POST', '/api/extension/links', $key, ['url' => $url, 'categories' => $four]);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('au plus trois catégories', self::violations($body));
    }

    public function testBrowserPageAddressIsRejected(): void
    {
        $this->api('POST', '/api/extension/links', $this->keyFor($this->member()), [
            'url' => 'chrome://extensions/',
            'categories' => [$this->categoryId('Agents')],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testUnknownCategoryIsRejected(): void
    {
        $body = $this->api('POST', '/api/extension/links', $this->keyFor($this->member()), [
            'url' => 'https://example.com/' . uniqid('inconnue-'),
            'categories' => [$this->categoryId('Agents'), 999_999],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('n\'existe plus', self::violations($body));
    }

    public function testDuplicateIsRefused(): void
    {
        $key = $this->keyFor($this->member());
        $payload = ['url' => 'https://example.com/' . uniqid('doublon-'), 'categories' => [$this->categoryId('IA')]];
        $this->api('POST', '/api/extension/links', $key, $payload);

        $this->api('POST', '/api/extension/links', $key, $payload);

        self::assertResponseStatusCodeSame(409);
    }

    private function links(): LinkRepository
    {
        $repository = self::getContainer()->get(LinkRepository::class);
        self::assertInstanceOf(LinkRepository::class, $repository);

        return $repository;
    }
}
