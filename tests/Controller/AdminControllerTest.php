<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Component\DomCrawler\Crawler;

final class AdminControllerTest extends SourceWebTestCase
{
    public function testPermanentAdminIsListedWithoutRemoveButton(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/administrateurs');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-test="admin-permanent"]', 'admin@example.com');
        self::assertSelectorNotExists('[data-test="admin-permanent"] [data-test="admin-remove"]');
    }

    public function testAddedEmailIsListedAndCannotBeAddedTwice(): void
    {
        $client = $this->loggedClient();
        $email = uniqid('admin-') . '@example.com';

        $this->submit($client, ['email' => strtoupper($email)], '/administrateurs', 'admin_email');
        self::assertResponseRedirects('/administrateurs');
        $client->followRedirect();
        self::assertSelectorTextContains('[data-test="admin-list"]', $email);

        $this->submit($client, ['email' => $email], '/administrateurs', 'admin_email');
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="admin-form"]', 'Cette personne est déjà admin.');

        $this->submit($client, ['email' => 'admin@example.com'], '/administrateurs', 'admin_email');
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="admin-form"]', 'Cette personne est déjà admin.');
    }

    public function testInvalidEmailIsRefused(): void
    {
        $client = $this->loggedClient();

        $this->submit($client, ['email' => 'pas-un-email'], '/administrateurs', 'admin_email');

        self::assertResponseStatusCodeSame(422);
    }

    public function testAddedMemberBecomesAdminAtOnceAndStopsBeingOneOnceRemoved(): void
    {
        $client = $this->loggedClient();
        $this->submit($client, ['email' => 'marie@example.com'], '/administrateurs', 'admin_email');
        self::assertResponseRedirects('/administrateurs');

        $this->logInAs($client, 'marie@example.com');
        $client->request('GET', '/sources');
        self::assertResponseIsSuccessful();

        $crawler = $client->request('GET', '/administrateurs');
        $form = $crawler
            ->filter('[data-test="admin-row"]')
            ->reduce(static fn(Crawler $row): bool => str_contains($row->text(), 'marie@example.com'))
            ->filter('form')
            ->form();
        $client->submit($form);
        self::assertResponseRedirects('/administrateurs');

        $client->request('GET', '/sources');
        self::assertResponseStatusCodeSame(403);
    }
}
