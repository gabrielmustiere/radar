<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;

final class AdminLinkControllerTest extends AdminLinkWebTestCase
{
    public function testMemberIsRefusedOnEveryAction(): void
    {
        $client = $this->loggedClient();
        $id = (int) $this->addedLink($client)->getId();
        $users = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $users);
        $member = $users->findOneBy(['email' => 'marie@example.com']);
        self::assertNotNull($member);
        $client->loginUser($member);

        $client->request('GET', sprintf('/admin/liens/%d', $id));
        self::assertResponseStatusCodeSame(403);
        $client->request('POST', sprintf('/admin/liens/%d/supprimer', $id));
        self::assertResponseStatusCodeSame(403);
        $client->request('POST', sprintf('/admin/liens/%d/relancer', $id));
        self::assertResponseStatusCodeSame(403);
        self::assertNotNull($this->find($id));
    }

    public function testShowsTitleLastAnnouncementAndBothActions(): void
    {
        $client = $this->loggedClient();
        $link = $this->addedLink($client);

        $client->request('GET', sprintf('/admin/liens/%d', $link->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('turbo-frame#link-modal-frame');
        self::assertSelectorNotExists('[data-test="link-modal"]');
        self::assertSelectorTextContains('[data-test="admin-link-title"]', 'Un article de test');
        self::assertSelectorTextContains('[data-test="admin-link-url"]', $link->getUrl());
        self::assertSelectorTextContains('[data-test="admin-link-announced"]', 'Dernière annonce le');
        self::assertSelectorExists('[data-test="admin-link-reannounce-form"][data-turbo-frame="_top"]');
        self::assertSelectorExists('[data-test="admin-link-remove-form"][data-turbo-frame="_top"]');
    }

    public function testRemovesLinkAndGoesBackToTheFeed(): void
    {
        $client = $this->loggedClient();
        $id = (int) $this->addedLink($client)->getId();

        $crawler = $client->request('GET', sprintf('/admin/liens/%d', $id));
        $client->submit($crawler->filter('[data-test="admin-link-remove-form"]')->form());

        self::assertResponseRedirects('/');
        self::assertNull($this->find($id));
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Lien « Un article de test » supprimé.');
        $client->request('GET', sprintf('/liens/%d', $id));
        self::assertResponseStatusCodeSame(404);
    }

    public function testRemovalSendsNothingToChat(): void
    {
        $client = $this->loggedClient();
        $id = (int) $this->addedLink($client)->getId();
        $crawler = $client->request('GET', sprintf('/admin/liens/%d', $id));

        $client->submit($crawler->filter('[data-test="admin-link-remove-form"]')->form());

        self::assertNotificationCount(0);
    }

    public function testRefusesRemovalWithoutValidToken(): void
    {
        $client = $this->loggedClient();
        $id = (int) $this->addedLink($client)->getId();

        $client->request('POST', sprintf('/admin/liens/%d/supprimer', $id), ['_token' => 'invalide']);

        // Un jeton CSRF invalide est une erreur d'authentification : le firewall renvoie vers la connexion.
        self::assertResponseRedirects('/login');
        self::assertNotNull($this->find($id));
    }

    public function testAnswersNotFoundForUnknownLink(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/admin/liens/999999');

        self::assertResponseStatusCodeSame(404);
    }
}
