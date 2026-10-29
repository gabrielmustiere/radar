<?php

declare(strict_types=1);

namespace App\Tests\Controller;

final class FeedTest extends LinkWebTestCase
{
    public function testLatestLinkOpensTheFeedUnderToday(): void
    {
        $client = $this->loggedClient();
        $url = 'https://example.com/' . uniqid('fil-');
        $this->submit($client, ['url' => $url, 'categories' => [$this->categoryId('Concevoir une interface')]]);

        $crawler = $client->request('GET', '/');

        self::assertSelectorTextContains(
            '[data-test="feed-day"]:first-child [data-test="feed-day-label"]',
            'Aujourd\'hui',
        );
        self::assertSelectorTextContains(self::FIRST_CARD, $url);
        $total = (int) $crawler->filter('[data-test="link-count"]')->text();
        self::assertGreaterThanOrEqual(count($crawler->filter('[data-test="link-card"]')), $total);
    }

    public function testCategoryPagesHoldTwentyFourLinks(): void
    {
        $client = $this->loggedClient();
        $name = 'Pagination ' . uniqid();
        $this->submit($client, ['url' => 'https://example.com/' . uniqid('p0-'), 'newCategory' => $name]);
        $categoryId = $this->categoryId($name);
        for ($index = 1; $index < 25; ++$index) {
            $this->submit($client, [
                'url' => 'https://example.com/' . uniqid('p' . $index . '-'),
                'categories' => [$categoryId],
            ]);
        }

        $client->request('GET', '/categories/' . $categoryId);
        self::assertSelectorCount(24, '[data-test="link-card"]');
        self::assertSelectorTextContains('[data-test="link-count"]', '25 liens');
        self::assertSelectorNotExists('[data-test="pager-previous"]');

        $client->clickLink('Plus anciens');
        self::assertSelectorCount(1, '[data-test="link-card"]');
        self::assertSelectorExists('[data-test="pager-previous"]');
        self::assertSelectorNotExists('[data-test="pager-next"]');

        $client->request('GET', sprintf('/categories/%d?page=3', $categoryId));
        self::assertResponseStatusCodeSame(404);
    }

    public function testSearchResultsArePaginatedKeepingTheQuery(): void
    {
        $client = $this->loggedClient();
        $marker = uniqid('lot');
        for ($index = 0; $index < 25; ++$index) {
            $this->submit($client, [
                'url' => sprintf('https://example.com/%s-%d', $marker, $index),
                'categories' => [$this->categoryId('Concevoir une interface')],
            ]);
        }

        $client->request('GET', '/recherche', ['q' => $marker]);
        self::assertSelectorCount(24, '[data-test="link-card"]');

        $client->clickLink('Plus anciens');
        self::assertSelectorCount(1, '[data-test="link-card"]');
        self::assertSame($marker, $client->getRequest()->query->getString('q'));
    }

    public function testInvalidPageIsNotFound(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/?page=0');

        self::assertResponseStatusCodeSame(404);
    }
}
