<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Link;
use App\Repository\UserRepository;
use App\Service\LinkUrlAnalyzer;
use App\Service\Preview\LinkPreview;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Liens créés avec un aperçu déjà construit (sans passer par les sources), retirés après chaque test.
 */
abstract class EnrichedLinkWebTestCase extends LinkWebTestCase
{
    /** @var list<Link> */
    private array $created = [];

    #[\Override]
    protected function tearDown(): void
    {
        $entityManager = $this->entityManager();
        foreach ($this->created as $link) {
            $managed = $entityManager->find(Link::class, $link->getId());
            if (null !== $managed) {
                $entityManager->remove($managed);
            }
        }

        $entityManager->flush();
        $this->created = [];
        parent::tearDown();
    }

    protected function enrichedLink(
        string $url,
        LinkPreview $preview,
        ?string $imagePath = null,
        ?string $faviconPath = null,
    ): Link {
        $users = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $users);
        $poster = $users->findOneBy(['email' => 'admin@example.com']);
        self::assertNotNull($poster);
        $analyzer = new LinkUrlAnalyzer();

        $link = new Link(
            $url,
            $analyzer->normalize($url),
            [],
            $poster,
            new \DateTimeImmutable(),
            $analyzer->sourceType($url),
        );
        $link->applyPreview($preview, $imagePath, $faviconPath, new \DateTimeImmutable());
        $link->markAnnounced(new \DateTimeImmutable());
        $this->entityManager()->persist($link);
        $this->entityManager()->flush();
        $this->created[] = $link;

        return $link;
    }

    protected function showCard(KernelBrowser $client, Link $link): string
    {
        $client->request('GET', sprintf('/liens/%d', $link->getId()));
        self::assertResponseIsSuccessful();

        return sprintf('#link-card-%d', $link->getId());
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
