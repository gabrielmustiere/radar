<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Source;
use App\Entity\SourceItem;
use App\Repository\LinkRepository;
use App\Repository\SourceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Sources suivies sur les flux simulés de `PreviewMockResponseFactory` (`https://example.com/feed/…`).
 * L'admin permanent des tests est `admin@example.com` (`.env.test`).
 */
abstract class SourceWebTestCase extends LinkWebTestCase
{
    protected function logInAs(KernelBrowser $client, string $email): void
    {
        $repository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);
        $user = $repository->findOneByEmail($email);
        self::assertNotNull($user);
        $client->loginUser($user);
    }

    /** @param list<string> $categories noms de catégories des fixtures */
    protected function follow(KernelBrowser $client, string $feedUrl, array $categories = ['Symfony']): void
    {
        $this->submit(
            $client,
            [
                'feedUrl' => $feedUrl,
                'categories' => array_map($this->categoryId(...), $categories),
            ],
            '/sources',
            'source',
        );
    }

    protected function source(string $feedUrl): Source
    {
        $repository = self::getContainer()->get(SourceRepository::class);
        self::assertInstanceOf(SourceRepository::class, $repository);
        $source = $repository->findOneByFeedUrl($feedUrl);
        self::assertNotNull($source);
        $this->entityManager()->refresh($source);

        return $source;
    }

    protected function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }

    protected function rowForm(KernelBrowser $client, string $feedUrl, string $button): Form
    {
        return $client
            ->request('GET', '/sources')
            ->filter('[data-test="source-row"]')
            ->reduce(static fn(Crawler $row): bool => str_contains($row->text(), $feedUrl))
            ->filter(sprintf('[data-test="%s"]', $button))
            ->form();
    }

    /** Fait comme si l'article n'avait jamais été vu : il est « nouveau » à la relève suivante. */
    protected function forget(Source $source, string $key): void
    {
        $item = $this
            ->entityManager()
            ->getRepository(SourceItem::class)
            ->findOneBy([
                'source' => $source,
                'itemKey' => $key,
            ]);
        self::assertNotNull($item);
        $this->entityManager()->remove($item);
        $this->entityManager()->flush();
    }

    protected function bus(): MessageBusInterface
    {
        $bus = self::getContainer()->get(MessageBusInterface::class);
        self::assertInstanceOf(MessageBusInterface::class, $bus);

        return $bus;
    }

    protected function linkRepository(): LinkRepository
    {
        $repository = self::getContainer()->get(LinkRepository::class);
        self::assertInstanceOf(LinkRepository::class, $repository);

        return $repository;
    }
}
