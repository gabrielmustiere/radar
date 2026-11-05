<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Link;
use App\Repository\CategoryRepository;
use App\Repository\LinkRepository;
use App\Repository\UserRepository;
use App\Service\LinkManager;
use App\Service\LinkOpeningRecorder;
use App\Service\Preview\LinkBannerStore;
use App\Tests\Controller\LinkWebTestCase;
use Doctrine\DBAL\Connection;
use League\Flysystem\FilesystemOperator;

/** Sur la vraie base : les clés étrangères SQLite étant inactives, seul ce niveau prouve l'effacement. */
final class LinkRemovalTest extends LinkWebTestCase
{
    public function testRemovesOpeningsAndCategoryLinksButKeepsTheCategory(): void
    {
        $client = $this->loggedClient();
        $categoryId = $this->createCategory(uniqid('Vide après suppression '));
        $url = 'https://example.com/' . uniqid('supprime-');
        $this->submit($client, ['url' => $url, 'categories' => [$categoryId]]);
        $link = $this->link($url);
        $linkId = (int) $link->getId();
        $this->record($link);
        self::assertSame(1, $this->rows('link_opening', $linkId));
        self::assertSame(1, $this->rows('link_category', $linkId));

        $this->manager()->remove($link);

        self::assertSame(0, $this->rows('link_opening', $linkId));
        self::assertSame(0, $this->rows('link_category', $linkId));
        self::assertNull($this->links()->find($linkId));
        $categories = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $categories);
        self::assertNotNull($categories->find($categoryId));
    }

    public function testRemovesTheBannerOfTheLink(): void
    {
        $client = $this->loggedClient();
        $client->disableReboot();
        $url = 'https://example.com/article-' . uniqid();
        $this->submit($client, ['url' => $url, 'categories' => [$this->categoryId('Agents')]]);
        $link = $this->link($url);
        $store = self::getContainer()->get(LinkBannerStore::class);
        self::assertInstanceOf(LinkBannerStore::class, $store);
        $name = $store->name($link);
        $storage = self::getContainer()->get('media.storage');
        self::assertInstanceOf(FilesystemOperator::class, $storage);
        $storage->write(LinkBannerStore::path($name), 'JPEG');

        $this->manager()->remove($link);

        self::assertFalse($storage->fileExists(LinkBannerStore::path($name)));
    }

    public function testFreesTheAddressOfARemovedLink(): void
    {
        $client = $this->loggedClient();
        $url = 'https://example.com/' . uniqid('readd-');
        $this->submit($client, ['url' => $url, 'categories' => [$this->categoryId('Agents')]]);

        $this->manager()->remove($this->link($url));
        self::assertNull($this->manager()->findDuplicate($url));

        $this->submit($client, ['url' => $url, 'categories' => [$this->categoryId('Agents')]]);

        self::assertResponseRedirects('/');
        self::assertNotNull($this->manager()->findDuplicate($url));
    }

    private function record(Link $link): void
    {
        $users = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $users);
        $member = $users->findOneBy(['email' => 'admin@example.com']);
        self::assertNotNull($member);
        $recorder = self::getContainer()->get(LinkOpeningRecorder::class);
        self::assertInstanceOf(LinkOpeningRecorder::class, $recorder);
        $recorder->record($link, $member);
    }

    private function rows(string $table, int $linkId): int
    {
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);

        return (int) $connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s WHERE link_id = ?', $table), [$linkId]);
    }

    private function link(string $url): Link
    {
        $link = $this->links()->findOneBy(['url' => $url]);
        self::assertNotNull($link);

        return $link;
    }

    private function links(): LinkRepository
    {
        $links = self::getContainer()->get(LinkRepository::class);
        self::assertInstanceOf(LinkRepository::class, $links);

        return $links;
    }

    private function manager(): LinkManager
    {
        $manager = self::getContainer()->get(LinkManager::class);
        self::assertInstanceOf(LinkManager::class, $manager);

        return $manager;
    }
}
