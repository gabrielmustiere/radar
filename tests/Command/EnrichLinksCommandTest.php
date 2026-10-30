<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\Link;
use App\Enum\Type\SourceType;
use App\Repository\LinkRepository;
use App\Repository\UserRepository;
use App\Service\Preview\LinkPreview;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class EnrichLinksCommandTest extends KernelTestCase
{
    private const string NEVER = 'https://example.com/article-jamais-tente';
    private const string PENDING = 'https://example.com/article-en-attente';
    private const string READY_STALE = 'https://example.com/article-pret-8-jours';
    private const string READY_FRESH = 'https://example.com/article-pret-1-jour';
    private const string FAILED_STALE = 'https://example.com/article-echec-2-heures';
    private const string FAILED_FRESH = 'https://example.com/article-echec-10-minutes';

    private EntityManagerInterface $entityManager;
    private LinkRepository $links;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        $links = self::getContainer()->get(LinkRepository::class);
        self::assertInstanceOf(LinkRepository::class, $links);
        $this->links = $links;
        $this->removeLinks();

        $this->persist(self::NEVER);
        $this->persist(self::PENDING);
        $this->persist(self::READY_STALE)->applyPreview(
            new LinkPreview(title: 'Vieux'),
            null,
            null,
            new \DateTimeImmutable('-8 days'),
        );
        $this->persist(self::READY_FRESH)->applyPreview(
            new LinkPreview(title: 'Récent'),
            null,
            null,
            new \DateTimeImmutable('-1 day'),
        );
        $this->persist(self::FAILED_STALE)->markPreviewUnavailable(new \DateTimeImmutable('-2 hours'));
        $this->persist(self::FAILED_FRESH)->markPreviewUnavailable(new \DateTimeImmutable('-10 minutes'));
        $this->entityManager->flush();
        // Lien antérieur à l'horodatage des aperçus : prêt, jamais daté.
        $this->entityManager->getConnection()->executeStatement("UPDATE link SET preview_status = 'ready' WHERE url = ?", [
            self::NEVER,
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->removeLinks();
        parent::tearDown();
    }

    public function testDryRunListsOnlyDueLinksAndSendsNothing(): void
    {
        $output = $this->enrich(['--dry-run' => true]);

        self::assertStringContainsString(self::NEVER, $output);
        self::assertStringContainsString(self::READY_STALE, $output);
        self::assertStringContainsString(self::FAILED_STALE, $output);
        self::assertStringNotContainsString(self::READY_FRESH, $output);
        self::assertStringNotContainsString(self::FAILED_FRESH, $output);
        self::assertStringNotContainsString(self::PENDING, $output);
        self::assertStringContainsString('Simulation', $output);
        self::assertNull($this->link(self::NEVER)->getPreviewFetchedAt());
    }

    public function testEnrichesDueLinks(): void
    {
        $this->enrich([]);

        self::assertSame('Un article de test', $this->link(self::NEVER)->getTitle());
        self::assertSame('Un article de test', $this->link(self::FAILED_STALE)->getTitle());
        self::assertSame('Récent', $this->link(self::READY_FRESH)->getTitle());
        self::assertNull($this->link(self::NEVER)->getAnnouncedAt(), 'Un rafraîchissement n\'est jamais annoncé.');
        self::assertNull($this->link(self::FAILED_STALE)->getAnnouncedAt());
    }

    public function testForceSelectsEveryLink(): void
    {
        $output = $this->enrich(['--dry-run' => true, '--force' => true]);

        self::assertStringContainsString(self::READY_FRESH, $output);
        self::assertStringContainsString(self::FAILED_FRESH, $output);
        self::assertStringContainsString(self::PENDING, $output);
    }

    /**
     * `CommandTester::execute()` plutôt que `runCommand()` : sa sortie de test ne gère pas les tableaux de SymfonyStyle.
     *
     * @param array<string, bool> $options
     */
    private function enrich(array $options): string
    {
        self::assertNotNull(self::$kernel);
        $tester = new CommandTester(new Application(self::$kernel)->find('app:links:enrich'));
        $tester->execute($options);
        $tester->assertCommandIsSuccessful();

        return $tester->getDisplay();
    }

    private function persist(string $url): Link
    {
        $users = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $users);
        $user = $users->findOneBy(['email' => 'admin@example.com']);
        self::assertNotNull($user);
        $link = new Link($url, $url, [], $user, new \DateTimeImmutable('-1 month'), SourceType::Article);
        $this->entityManager->persist($link);

        return $link;
    }

    private function link(string $url): Link
    {
        $this->entityManager->clear();
        $link = $this->links->findOneByNormalizedUrl($url);
        self::assertNotNull($link);

        return $link;
    }

    private function removeLinks(): void
    {
        $repository = $this->links;
        foreach ([
            self::NEVER,
            self::PENDING,
            self::READY_STALE,
            self::READY_FRESH,
            self::FAILED_STALE,
            self::FAILED_FRESH,
        ] as $url) {
            $link = $repository->findOneByNormalizedUrl($url);
            if (null !== $link) {
                $this->entityManager->remove($link);
            }
        }

        $this->entityManager->flush();
    }
}
