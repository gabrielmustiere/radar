<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\LinkRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class LinkSeedTest extends LinkWebTestCase
{
    public function testSeedEntryIsReachableFromAddScreen(): void
    {
        $client = $this->loggedClient();
        $client->request('GET', '/liens/ajouter');

        $client->clickLink('Reprendre un lien de Chat');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Reprendre un lien de Chat');
    }

    public function testSeededLinkKeepsOriginalPosterAndDate(): void
    {
        $client = $this->loggedClient();
        $url = 'https://example.com/' . uniqid('chat-');

        $this->seed($client, [
            'url' => $url,
            'postedAt' => '2026-03-15',
            'poster' => $this->userId('marie@example.com'),
        ]);

        self::assertResponseRedirects('/');
        $repository = self::getContainer()->get(LinkRepository::class);
        self::assertInstanceOf(LinkRepository::class, $repository);
        $link = $repository->findOneBy(['url' => $url]);
        self::assertNotNull($link);
        self::assertTrue($link->isSeeded());
        self::assertSame('2026-03-15', $link->getPostedAt()->format('Y-m-d'));
        self::assertSame('marie', $link->getPostedBy()->getDisplayName());

        $client->request('GET', sprintf('/liens/%d', $link->getId()));
        self::assertSelectorTextContains('[data-test="link-poster"]', 'marie');
        self::assertSelectorTextContains('[data-test="link-date"]', '15/03/2026');
        self::assertSelectorTextContains('[data-test="link-seeded"]', 'repris de Chat');
    }

    public function testFutureDateIsRefused(): void
    {
        $client = $this->loggedClient();

        $this->seed($client, [
            'url' => 'https://example.com/futur',
            'postedAt' => new \DateTimeImmutable('+2 days')->format('Y-m-d'),
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains(
            '[data-test="link-form"]',
            'La date d\'origine ne peut pas être dans le futur.',
        );
    }

    public function testMissingDateIsRefused(): void
    {
        $client = $this->loggedClient();

        $this->seed($client, ['url' => 'https://example.com/sans-date', 'postedAt' => '']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains(
            '[data-test="link-form"]',
            'Indiquez la date à laquelle le lien a été posté dans Chat.',
        );
    }

    public function testAlreadySeededLinkIsRefusedWithExistingCard(): void
    {
        $client = $this->loggedClient();
        $url = 'https://example.com/' . uniqid('deja-repris-');
        $this->seed($client, ['url' => $url, 'postedAt' => '2026-02-01']);

        $this->seed($client, ['url' => $url . '?utm_source=chat', 'postedAt' => '2026-02-02']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="link-duplicate"]', 'Ce lien est déjà dans Radar');
    }

    /** @param array<string, mixed> $values */
    private function seed(KernelBrowser $client, array $values): void
    {
        $this->submit(
            $client,
            [
                'poster' => $this->userId('admin@example.com'),
                ...$values,
                'categories' => [$this->categoryId('Architecture')],
            ],
            '/liens/reprendre',
            'historical_link',
        );
    }

    private function userId(string $email): int
    {
        $repository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);

        return (int) $repository->findOneBy(['email' => $email])?->getId();
    }
}
