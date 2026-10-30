<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Source;

/** Relève lancée à la demande depuis la page des sources. */
final class SourceFetchTest extends SourceWebTestCase
{
    public function testFetchOnDemandPublishesNewArticlesAndSaysHowMany(): void
    {
        $client = $this->loggedClient();
        $run = uniqid();
        $feedUrl = 'https://example.com/feed/journal?run=' . $run;
        $this->follow($client, $feedUrl);
        $this->forget($this->source($feedUrl), $run . '-2');

        $client->submit($this->rowForm($client, $feedUrl, 'source-fetch'));

        self::assertResponseRedirects('/sources');
        self::assertNotificationCount(1);
        $client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', sprintf(
            'Relève de « Journal %s » sur 1 h : 1 article publié.',
            $run,
        ));
        self::assertNotNull($this->linkRepository()->findOneByNormalizedUrl(
            'https://example.com/article-' . $run . '-2',
        ));

        $client->submit($this->rowForm($client, $feedUrl, 'source-fetch'));
        $client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', sprintf(
            'Relève de « Journal %s » sur 1 h : aucun nouvel article.',
            $run,
        ));
    }

    public function testFetchOnDemandGoesBackOverTheChosenWindow(): void
    {
        $client = $this->loggedClient();
        $run = uniqid();
        $feedUrl = 'https://example.com/feed/journal?run=' . $run;
        $this->follow($client, $feedUrl);
        $article = static fn(int $number): string => sprintf('https://example.com/article-%s-%d', $run, $number);

        $client->submit($this->rowForm($client, $feedUrl, 'source-fetch'), ['since' => '1h']);
        self::assertNull($this->linkRepository()->findOneByNormalizedUrl($article(2)));

        $client->submit($this->rowForm($client, $feedUrl, 'source-fetch'), ['since' => '12h']);
        $client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', sprintf(
            'Relève de « Journal %s » sur 12 h : 1 article publié.',
            $run,
        ));
        self::assertNotNull($this->linkRepository()->findOneByNormalizedUrl($article(2)));
        self::assertNull($this->linkRepository()->findOneByNormalizedUrl($article(1)));

        $client->submit($this->rowForm($client, $feedUrl, 'source-fetch'), ['since' => '36h']);
        $client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', sprintf(
            'Relève de « Journal %s » sur 36 h : 1 article publié.',
            $run,
        ));
        self::assertNotNull($this->linkRepository()->findOneByNormalizedUrl($article(1)));
    }

    public function testFetchOnDemandOfABrokenFeedShowsTheError(): void
    {
        $client = $this->loggedClient();
        $run = uniqid();
        $brokenUrl = 'https://example.com/feed/broken?run=' . $run;
        $this->entityManager()->persist(new Source($brokenUrl, 'Flux cassé ' . $run, [], new \DateTimeImmutable()));
        $this->entityManager()->flush();

        $client->submit($this->rowForm($client, $brokenUrl, 'source-fetch'));

        self::assertResponseRedirects('/sources');
        $client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', sprintf('Relève de « Flux cassé %s » impossible', $run));
        self::assertSelectorTextContains('[role="alert"]', 'ce n\'est pas du XML valide');
    }

    public function testRemovedSourceCannotBeFetchedOnDemand(): void
    {
        $client = $this->loggedClient();
        $feedUrl = 'https://example.com/feed/journal?run=' . uniqid();
        $this->follow($client, $feedUrl);
        $form = $this->rowForm($client, $feedUrl, 'source-fetch');
        $this->source($feedUrl)->remove(new \DateTimeImmutable());
        $this->entityManager()->flush();

        $client->submit($form);

        self::assertResponseStatusCodeSame(404);
    }
}
