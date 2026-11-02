<?php

declare(strict_types=1);

namespace App\Tests\Controller;

final class LinkDuplicateTest extends LinkWebTestCase
{
    public function testDuplicateIsRefusedWithExistingCard(): void
    {
        $client = $this->loggedClient();
        $slug = uniqid('doublon-');
        $this->submit($client, [
            'url' => 'https://example.com/' . $slug,
            'categories' => [$this->categoryId('Symfony')],
        ]);
        self::assertResponseRedirects('/');

        $this->submit($client, [
            'url' => 'http://www.example.com/' . $slug . '/?utm_source=chat#:~:text=haut',
            'categories' => [$this->categoryId('Agents')],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="link-duplicate"]', 'Ce lien est déjà dans Radar');
        self::assertSelectorTextContains(
            '[data-test="link-duplicate"] [data-test="link-card"]',
            'https://example.com/' . $slug,
        );
        self::assertSelectorTextContains('[data-test="link-form"]', 'Ce lien est déjà dans Radar.');

        $client->clickLink('Voir la carte');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-test="link-card"]', 'https://example.com/' . $slug);
    }

    public function testYoutubeShortLinkIsADuplicate(): void
    {
        $client = $this->loggedClient();
        $id = uniqid('vid');
        $this->submit($client, [
            'url' => 'https://www.youtube.com/watch?v=' . $id,
            'categories' => [$this->categoryId('Symfony')],
        ]);

        $this->submit($client, [
            'url' => 'https://youtu.be/' . $id,
            'categories' => [$this->categoryId('Symfony')],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('[data-test="link-duplicate"]');
    }

    public function testSignificantParameterMakesADistinctLink(): void
    {
        $client = $this->loggedClient();
        $path = 'https://example.com/' . uniqid('liste-');
        $this->submit($client, [
            'url' => $path . '?page=1',
            'categories' => [$this->categoryId('Symfony')],
        ]);

        $this->submit($client, [
            'url' => $path . '?page=2',
            'categories' => [$this->categoryId('Symfony')],
        ]);

        self::assertResponseRedirects('/');
    }

    public function testUnknownCardIsNotFound(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/liens/999999');

        self::assertResponseStatusCodeSame(404);
    }
}
