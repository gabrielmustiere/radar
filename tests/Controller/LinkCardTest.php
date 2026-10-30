<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Service\Preview\PreviewImageStore;

final class LinkCardTest extends LinkWebTestCase
{
    public function testCardShowsPreviewBuiltAfterAdd(): void
    {
        $client = $this->loggedClient();

        $this->submit($client, [
            'url' => 'https://example.com/article-' . uniqid(),
            'categories' => [$this->categoryId('Agents')],
        ]);

        $client->followRedirect();
        $card = self::FIRST_CARD;
        self::assertSelectorTextContains($card . ' [data-test="link-title"]', 'Un article de test');
        self::assertSelectorTextContains(
            $card . ' [data-test="link-description"]',
            'Ce qu\'il faut savoir sur les tests.',
        );
        self::assertSelectorExists(sprintf(
            '%s img[src="/media/%s"][loading="lazy"][alt="Un article de test"]',
            $card,
            PreviewImageStore::previewPath('https://example.com/cover.png'),
        ));
        self::assertSelectorNotExists($card . ' img[src^="https://"]');
        self::assertSelectorTextContains($card . ' [data-test="link-source"]', 'Article');
        self::assertSelectorTextContains($card . ' [data-test="link-category"]', 'Agents');
    }

    public function testCardFallsBackWhenSourceHasNoPreview(): void
    {
        $client = $this->loggedClient();
        $url = 'https://github.com/acme/' . uniqid();

        $this->submit($client, ['url' => $url, 'categories' => [$this->categoryId('Agents')]]);

        $client->followRedirect();
        $card = self::FIRST_CARD;
        self::assertSelectorExists($card . '[data-preview-status="unavailable"]');
        self::assertSelectorTextContains($card . ' [data-test="link-title"]', $url);
        self::assertSelectorTextContains($card . ' [data-test="link-source"]', 'Dépôt GitHub');
        self::assertSelectorTextContains($card . ' [data-test="link-card-fallback"]', 'github.com');
        self::assertSelectorNotExists($card . ' img');
    }
}
