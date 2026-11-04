<?php

declare(strict_types=1);

namespace App\Tests\Controller;

final class AdminLinkReannounceTest extends AdminLinkWebTestCase
{
    public function testReannouncesASeededLinkAndConfirmsItIsQueued(): void
    {
        $client = $this->loggedClient();
        $id = (int) $this->seededLink()->getId();

        $crawler = $client->request('GET', sprintf('/admin/liens/%d', $id));
        self::assertSelectorTextContains('[data-test="admin-link-announced"]', 'Jamais annoncé');
        $client->submit($crawler->filter('[data-test="admin-link-reannounce-form"]')->form());

        self::assertResponseRedirects('/');
        self::assertNotificationCount(1);
        self::assertNotNull($this->find($id)?->getAnnouncedAt());
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'mise en file');

        $this->remove($id);
    }

    public function testRefusesReannouncementWithoutValidToken(): void
    {
        $client = $this->loggedClient();
        $id = (int) $this->seededLink()->getId();

        $client->request('POST', sprintf('/admin/liens/%d/relancer', $id), ['_token' => 'invalide']);

        self::assertResponseRedirects('/login');
        self::assertNull($this->find($id)?->getAnnouncedAt());

        $this->remove($id);
    }

    public function testHidesReannouncementWhilePreviewIsPendingAndRefusesItDirectly(): void
    {
        $client = $this->loggedClient();
        $id = (int) $this->pendingLink()->getId();
        // Jeton pris sur une carte prête : le refus vient bien de la règle, pas du CSRF.
        $crawler = $client->request('GET', sprintf('/admin/liens/%d', $this->addedLink($client)->getId()));
        $token = (string) $crawler
            ->filter('[data-test="admin-link-reannounce-form"] input[name="_token"]')
            ->attr('value');

        $client->request('GET', sprintf('/admin/liens/%d', $id));
        self::assertSelectorExists('[data-test="admin-link-pending"]');
        self::assertSelectorNotExists('[data-test="admin-link-reannounce-form"]');

        $client->request('POST', sprintf('/admin/liens/%d/relancer', $id), ['_token' => $token]);

        self::assertResponseRedirects('/');
        self::assertNotificationCount(0);
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'encore en construction');

        $this->remove($id);
    }
}
