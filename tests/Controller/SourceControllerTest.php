<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\SourceItem;
use App\Repository\LinkRepository;
use Symfony\Component\DomCrawler\Crawler;

final class SourceControllerTest extends SourceWebTestCase
{
    public function testMemberWhoIsNotAdminSeesNeitherTheMenuNorThePages(): void
    {
        $client = self::createClient();
        $this->logInAs($client, 'marie@example.com');

        $client->request('GET', '/');
        self::assertSelectorNotExists('[data-test="nav-sources"]');
        self::assertSelectorNotExists('[data-test="nav-admins"]');

        $client->request('GET', '/sources');
        self::assertResponseStatusCodeSame(403);
        $client->request('GET', '/administrateurs');
        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminSeesBothMenuEntries(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/');

        self::assertSelectorExists('[data-test="nav-sources"]');
        self::assertSelectorExists('[data-test="nav-admins"]');
    }

    public function testFollowedFeedIsListedWithItsNameAndPublishesNothingYet(): void
    {
        $client = $this->loggedClient();
        $linkRepository = self::getContainer()->get(LinkRepository::class);
        self::assertInstanceOf(LinkRepository::class, $linkRepository);
        $links = $linkRepository->count();
        $feedUrl = 'https://example.com/feed/symfony?run=' . uniqid();

        $this->follow($client, $feedUrl);

        self::assertResponseRedirects('/sources');
        $client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'Source « Symfony Blog » suivie.');
        $row = $client
            ->getCrawler()
            ->filter('[data-test="source-row"]')
            ->reduce(static fn(Crawler $row): bool => str_contains($row->text(), $feedUrl));
        self::assertCount(1, $row);
        self::assertStringContainsString('Symfony Blog', $row->filter('[data-test="source-name"]')->text());
        self::assertStringContainsString('Pas encore relevée', $row->text());
        self::assertSame($links, $linkRepository->count());
        self::assertCount(3, $this
            ->entityManager()
            ->getRepository(SourceItem::class)
            ->findBy([
                'source' => $this->source($feedUrl),
            ]));
    }

    public function testUnreadableFeedIsRefusedWithItsReason(): void
    {
        $client = $this->loggedClient();

        $this->follow($client, 'https://example.com/feed/broken?run=' . uniqid());
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="source-form"]', 'ce n\'est pas du XML valide');

        $this->follow($client, 'https://example.com/feed/page?run=' . uniqid());
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="source-form"]', 'ne mène pas à un flux RSS ou Atom');
    }

    public function testFeedFollowedTwiceIsRefused(): void
    {
        $client = $this->loggedClient();
        $feedUrl = 'https://example.com/feed/journal?run=' . uniqid();
        $this->follow($client, $feedUrl);

        $this->follow($client, $feedUrl);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="source-form"]', 'Ce flux est déjà suivi.');
    }

    public function testCategoryIsRequired(): void
    {
        $client = $this->loggedClient();

        $this->follow($client, 'https://example.com/feed/journal?run=' . uniqid(), []);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="source-categories"]', 'Choisissez au moins une catégorie.');
    }

    public function testRemovedSourceLeavesTheListAndIsReactivatedWhenFollowedAgain(): void
    {
        $client = $this->loggedClient();
        $feedUrl = 'https://example.com/feed/journal?run=' . uniqid();
        $this->follow($client, $feedUrl);
        $id = $this->source($feedUrl)->getId();

        $form = $this->rowForm($client, $feedUrl, 'source-remove');
        $client->submit($form);

        self::assertResponseRedirects('/sources');
        $client->followRedirect();
        self::assertSelectorTextNotContains('[data-test="source-list"]', $feedUrl);
        $removed = $this->source($feedUrl);
        self::assertFalse($removed->isActive());
        $removed->markFetched(new \DateTimeImmutable());
        $removed->markFailed('Flux injoignable : délai dépassé', new \DateTimeImmutable());
        $this->entityManager()->flush();

        $this->follow($client, $feedUrl, ['IA']);

        self::assertResponseRedirects('/sources');
        $source = $this->source($feedUrl);
        self::assertTrue($source->isActive());
        self::assertSame($id, $source->getId());
        self::assertNull($source->getLastFetchedAt());
        self::assertNull($source->getLastError());
        self::assertSame(
            ['IA'],
            $source
                ->getCategories()
                ->map(static fn(Category $category): string => $category->getName())
                ->getValues(),
        );
    }
}
