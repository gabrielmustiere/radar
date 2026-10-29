<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;

final class LinkAnnouncementTest extends LinkWebTestCase
{
    public function testAddedLinkIsAnnouncedOnceWithItsCard(): void
    {
        $client = $this->loggedClient();
        $url = 'https://example.com/article-' . uniqid('annonce-');

        $this->submit($client, [
            'url' => $url,
            'categories' => [$this->categoryId('Coder avec un agent IA')],
            'context' => 'À lire.',
        ]);

        self::assertResponseRedirects('/');
        self::assertQueuedNotificationCount(1);
        self::assertNotificationCount(1);
        $message = self::getNotifierMessage();
        self::assertNotNull($message);
        $text = $message->getSubject();
        self::assertStringContainsString('*Un article de test*', $text);
        self::assertStringContainsString('Coder avec un agent IA · posté par admin', $text);
        self::assertStringContainsString('_« À lire. »_', $text);
        self::assertMatchesRegularExpression('#<http://localhost/liens/\d+\|Voir dans Radar>#', $text);
    }

    public function testSeededLinkIsNotAnnounced(): void
    {
        $client = $this->loggedClient();
        $repository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);

        $this->submit(
            $client,
            [
                'url' => 'https://example.com/' . uniqid('repris-'),
                'categories' => [$this->categoryId('Coder avec un agent IA')],
                'postedAt' => '2026-01-10',
                'poster' => (string) $repository->findOneBy(['email' => 'admin@example.com'])?->getId(),
            ],
            '/liens/reprendre',
            'historical_link',
        );

        self::assertResponseRedirects('/');
        self::assertNotificationCount(0);
        self::assertQueuedNotificationCount(0);
    }
}
