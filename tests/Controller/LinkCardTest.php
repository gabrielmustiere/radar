<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;
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

    public function testAdminFindsTheActionsOnTheCardsOfTheFeedAndOfTheDetailPage(): void
    {
        $client = $this->loggedClient();
        $url = 'https://example.com/article-' . uniqid();
        $this->submit($client, ['url' => $url, 'categories' => [$this->categoryId('Agents')]]);

        $crawler = $client->followRedirect();

        $action = (string) $crawler->filter(self::FIRST_CARD . ' [data-test="link-admin-actions"]')->attr('href');
        self::assertSame(
            'link-modal-frame',
            $crawler->filter('[data-test="link-admin-actions"]')->attr('data-turbo-frame'),
        );
        $client->request('GET', str_replace(search: '/admin', replace: '', subject: $action));
        self::assertSelectorExists('[data-test="link-card"] [data-test="link-admin-actions"]');
    }

    public function testMemberSeesNoAdminActions(): void
    {
        $client = $this->loggedClient();
        $url = 'https://example.com/article-' . uniqid();
        $this->submit($client, ['url' => $url, 'categories' => [$this->categoryId('Agents')]]);
        $users = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $users);
        $member = $users->findOneBy(['email' => 'marie@example.com']);
        self::assertNotNull($member);
        $client->loginUser($member);

        $client->request('GET', '/');

        self::assertSelectorExists(self::FIRST_CARD);
        self::assertSelectorNotExists('[data-test="link-admin-actions"]');
    }

    public function testCardFallsBackWhenSourceHasNoPreview(): void
    {
        $client = $this->loggedClient();
        $url = 'https://github.com/acme/' . uniqid();

        $this->submit($client, ['url' => $url, 'categories' => [$this->categoryId('Agents')]]);

        $client->followRedirect();
        $card = self::FIRST_CARD;
        self::assertSelectorExists($card . '[data-preview-status="unavailable"]');
        self::assertSelectorTextSame($card . ' [data-test="link-title"]', substr($url, strlen('https://github.com/')));
        self::assertSelectorTextContains($card . ' [data-test="link-source"]', 'Dépôt GitHub');
        self::assertSelectorTextSame($card . ' [data-test="link-repository-owner"]', 'acme /');
        self::assertSelectorNotExists($card . ' img');
    }
}
