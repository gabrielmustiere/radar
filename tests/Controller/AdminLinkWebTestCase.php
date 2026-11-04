<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Link;
use App\Enum\Type\SourceType;
use App\Repository\LinkRepository;
use App\Repository\UserRepository;
use App\Service\LinkManager;
use App\Service\Preview\LinkPreview;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Actions admin sur un lien. L'admin permanent des tests est `admin@example.com` (`.env.test`).
 */
abstract class AdminLinkWebTestCase extends LinkWebTestCase
{
    protected function addedLink(KernelBrowser $client): Link
    {
        $url = 'https://example.com/article-' . uniqid();
        $this->submit($client, ['url' => $url, 'categories' => [$this->categoryId('Agents')]]);
        $link = $this->links()->findOneBy(['url' => $url]);
        self::assertNotNull($link);

        return $link;
    }

    protected function pendingLink(): Link
    {
        return $this->persisted($this->unsavedLink());
    }

    protected function seededLink(): Link
    {
        $link = $this->unsavedLink(seeded: true);
        $link->applyPreview(new LinkPreview(title: 'Repris'), null, null, new \DateTimeImmutable());

        return $this->persisted($link);
    }

    protected function find(int $id): ?Link
    {
        $this->entityManager()->clear();

        return $this->links()->find($id);
    }

    protected function remove(int $id): void
    {
        $link = $this->find($id);
        self::assertNotNull($link);
        $manager = self::getContainer()->get(LinkManager::class);
        self::assertInstanceOf(LinkManager::class, $manager);
        $manager->remove($link);
    }

    private function unsavedLink(bool $seeded = false): Link
    {
        $users = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $users);
        $poster = $users->findOneBy(['email' => 'admin@example.com']);
        self::assertNotNull($poster);
        $url = 'https://example.com/article-' . uniqid();

        return new Link($url, $url, [], $poster, new \DateTimeImmutable(), SourceType::Article, seeded: $seeded);
    }

    private function persisted(Link $link): Link
    {
        $this->entityManager()->persist($link);
        $this->entityManager()->flush();

        return $link;
    }

    private function links(): LinkRepository
    {
        $links = self::getContainer()->get(LinkRepository::class);
        self::assertInstanceOf(LinkRepository::class, $links);

        return $links;
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
