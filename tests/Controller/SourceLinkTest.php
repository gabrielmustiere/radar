<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Source;
use App\Entity\SourceItem;
use App\Message\FetchSource;
use App\Message\FetchSources;
use Symfony\Component\DomCrawler\Crawler;

/** Relève de bout en bout : en test, les messages sont traités sur-le-champ (`sync://`). */
final class SourceLinkTest extends SourceWebTestCase
{
    public function testNewArticleIsPublishedClassifiedMarkedAndAnnouncedOnce(): void
    {
        $client = $this->loggedClient();
        $run = uniqid();
        $feedUrl = 'https://example.com/feed/journal?run=' . $run;
        $this->follow($client, $feedUrl, ['Agents']);
        $source = $this->source($feedUrl);
        $this->forget($source, $run . '-2');

        $this->bus()->dispatch(new FetchSource((int) $source->getId()));

        $link = $this->linkRepository()->findOneByNormalizedUrl('https://example.com/article-' . $run . '-2');
        self::assertNotNull($link);
        self::assertSame('radar', $link->getPostedBy()->getDisplayName());
        self::assertSame($source->getId(), $link->getSource()?->getId());
        self::assertNull($link->getContext());
        self::assertFalse($link->isSeeded());
        self::assertSame(
            ['Agents'],
            $link
                ->getCategories()
                ->map(static fn(Category $category): string => $category->getName())
                ->getValues(),
        );
        self::assertNull($this->linkRepository()->findOneByNormalizedUrl('https://example.com/article-' . $run . '-1'));
        self::assertNotNull($this->source($feedUrl)->getLastFetchedAt());

        self::assertNotificationCount(1);
        $message = self::getNotifierMessage();
        self::assertNotNull($message);
        self::assertStringContainsString('Agents · posté par radar via Journal ' . $run, $message->getSubject());

        $client->request('GET', '/?q=' . $run);
        self::assertSelectorTextContains('[data-test="link-card"] [data-test="link-auto"]', 'auto · Journal ' . $run);
        self::assertSelectorTextContains('[data-test="link-card"] [data-test="link-poster"]', 'radar');
    }

    public function testArticleAlreadySeenIsNeverPublishedAgain(): void
    {
        $client = $this->loggedClient();
        $run = uniqid();
        $feedUrl = 'https://example.com/feed/journal?run=' . $run;
        $this->follow($client, $feedUrl);
        $links = $this->linkRepository()->count();

        $this->bus()->dispatch(new FetchSource((int) $this->source($feedUrl)->getId()));

        self::assertSame($links, $this->linkRepository()->count());
        self::assertNotificationCount(0);
    }

    public function testArticleAlreadyInRadarIsOnlyMarkedSeen(): void
    {
        $client = $this->loggedClient();
        $run = uniqid();
        $feedUrl = 'https://example.com/feed/journal?run=' . $run;
        $this->follow($client, $feedUrl);
        $source = $this->source($feedUrl);
        $this->forget($source, $run . '-2');
        $this->submit($client, [
            'url' => 'https://www.example.com/article-' . $run . '-2',
            'categories' => [$this->categoryId('IA')],
        ]);
        self::assertResponseRedirects('/');

        $this->bus()->dispatch(new FetchSource((int) $source->getId()));

        $link = $this->linkRepository()->findOneByNormalizedUrl('https://example.com/article-' . $run . '-2');
        self::assertNotNull($link);
        self::assertNull($link->getSource());
        self::assertSame('admin', $link->getPostedBy()->getDisplayName());
        self::assertNotNull(
            $this
                ->entityManager()
                ->getRepository(SourceItem::class)
                ->findOneBy([
                    'source' => $source,
                    'itemKey' => $run . '-2',
                ]),
        );
    }

    public function testBrokenFeedIsReportedWithoutBlockingTheOthers(): void
    {
        $client = $this->loggedClient();
        $run = uniqid();
        $feedUrl = 'https://example.com/feed/journal?run=' . $run;
        $this->follow($client, $feedUrl);
        $this->forget($this->source($feedUrl), $run . '-1');
        $brokenUrl = 'https://example.com/feed/broken?run=' . $run;
        $broken = new Source($brokenUrl, 'Flux cassé ' . $run, [], new \DateTimeImmutable());
        $this->entityManager()->persist($broken);
        $this->entityManager()->flush();

        $this->bus()->dispatch(new FetchSources());

        self::assertNotNull($this->linkRepository()->findOneByNormalizedUrl(
            'https://example.com/article-' . $run . '-1',
        ));
        $broken = $this->source($brokenUrl);
        self::assertNull($broken->getLastFetchedAt());
        self::assertStringContainsString('ce n\'est pas du XML valide', (string) $broken->getLastError());

        $client->request('GET', '/sources');
        $row = $client
            ->getCrawler()
            ->filter('[data-test="source-row"]')
            ->reduce(static fn(Crawler $row): bool => str_contains($row->text(), $brokenUrl));
        self::assertStringContainsString(
            'ce n\'est pas du XML valide',
            $row->filter('[data-test="source-error"]')->text(),
        );
    }

    public function testRemovedSourceIsNoLongerFetched(): void
    {
        $client = $this->loggedClient();
        $run = uniqid();
        $feedUrl = 'https://example.com/feed/journal?run=' . $run;
        $this->follow($client, $feedUrl);
        $source = $this->source($feedUrl);
        $this->forget($source, $run . '-2');
        $source->remove(new \DateTimeImmutable());
        $this->entityManager()->flush();

        $this->bus()->dispatch(new FetchSources());
        $this->bus()->dispatch(new FetchSource((int) $source->getId()));

        self::assertNull($this->linkRepository()->findOneByNormalizedUrl('https://example.com/article-' . $run . '-2'));
        self::assertNull($this->source($feedUrl)->getLastFetchedAt());
    }

    public function testRadarAccountCannotLogIn(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');

        $client->submitForm('Se connecter', ['_username' => 'radar@radar.invalid', '_password' => '!']);

        self::assertResponseRedirects('/login');
    }

    public function testFixtureLinksOfARemovedSourceKeepTheirBadge(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/?q=' . rawurlencode('LLM Voice'));

        self::assertSelectorTextContains('[data-test="link-card"] [data-test="link-auto"]', 'auto · Martin Fowler');
        self::assertStringContainsString(
            'ring-orange-200',
            (string) $client->getCrawler()->filter('[data-test="link-card"]')->attr('class'),
        );
    }
}
