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
            'categories' => [$this->categoryId('Agents')],
            'context' => 'À lire.',
        ]);

        self::assertResponseRedirects('/');
        self::assertQueuedNotificationCount(1);
        self::assertNotificationCount(1);
        $message = self::getNotifierMessage();
        self::assertNotNull($message);
        $text = $message->getSubject();
        self::assertStringContainsString(
            'Un article de test · Article · example.com · Agents · Posté par admin',
            $text,
        );
        $card = (string) json_encode(
            $message->getOptions()?->toArray(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
        self::assertStringContainsString('<i>À lire.</i>', $card);
        self::assertMatchesRegularExpression('#"url":"http://localhost/liens/\d+"#', $card);
        self::assertStringContainsString('"iconUrl":"http://localhost/chat/icons/article.png"', $card);
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
                'categories' => [$this->categoryId('Agents')],
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
