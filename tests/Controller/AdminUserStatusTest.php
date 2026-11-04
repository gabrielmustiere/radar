<?php

declare(strict_types=1);

namespace App\Tests\Controller;

/** Statut admin et activation des comptes, avec les verrous sur soi-même et sur l'admin permanent. */
final class AdminUserStatusTest extends AdminUserWebTestCase
{
    public function testGrantedAdminReachesTheSourcesAtTheNextRequest(): void
    {
        $client = self::createClient();
        $account = $this->account();
        $this->logInAs($client, $account->getUserIdentifier());
        $client->request('GET', '/sources');
        self::assertResponseStatusCodeSame(403);

        $this->logInAs($client, 'admin@example.com');
        $this->submit(
            $client,
            ['email' => $account->getUserIdentifier(), 'admin' => '1'],
            $this->editPath($account),
            'user_edition',
        );
        self::assertResponseRedirects('/admin/comptes');
        $crawler = $client->request('GET', '/admin/comptes');
        self::assertCount(
            1,
            $this->row($crawler, $account->getUserIdentifier())->filter('[data-test="user-admin-badge"]'),
        );

        $this->logInAs($client, $account->getUserIdentifier());
        $client->request('GET', '/sources');
        self::assertResponseIsSuccessful();
    }

    public function testDeactivatedAccountIsCutOffButKeepsItsLinks(): void
    {
        $client = self::createClient();
        $account = $this->account();
        $url = 'https://example.com/' . uniqid('compte-desactive-');
        $this->logInAs($client, $account->getUserIdentifier());
        $this->submit($client, ['url' => $url, 'categories' => [$this->categoryId('Symfony')]]);
        self::assertResponseRedirects('/');

        $this->logInAs($client, 'admin@example.com');
        $client->submit($this->rowButton($client, $account, 'user-deactivate')->form());
        self::assertResponseRedirects('/admin/comptes');
        $client->followRedirect();
        self::assertSelectorTextContains('body', sprintf('Compte de %s désactivé.', $account->getUserIdentifier()));
        self::assertStringContainsString(
            'désactivé',
            $this
                ->row($client->getCrawler(), $account->getUserIdentifier())
                ->filter('[data-test="user-state"]')
                ->text(),
        );

        $this->logInAs($client, $account->getUserIdentifier());
        $client->request('GET', '/');
        self::assertResponseRedirects('/login');

        $this->logInWithForm($client, $account->getUserIdentifier(), self::PASSWORD);
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'Ce compte est désactivé.');

        $this->logInAs($client, 'admin@example.com');
        $client->request('GET', '/?q=' . rawurlencode($url));
        self::assertSelectorTextContains(self::FIRST_CARD . ' [data-test="link-poster"]', $account->getDisplayName());
    }

    public function testDeactivationStaysHiddenWithoutTheRightPassword(): void
    {
        $client = self::createClient();
        $account = $this->account();
        $account->deactivate();
        $this->entityManager()->flush();

        $this->logInWithForm($client, $account->getUserIdentifier(), 'mauvais-mot-de-passe');
        self::assertResponseRedirects('/login');
        $client->followRedirect();

        self::assertSelectorExists('[role="alert"]');
        self::assertSelectorTextNotContains('[role="alert"]', 'désactivé');
    }

    public function testReactivatedAccountLogsInAgainWithItsPassword(): void
    {
        $client = $this->loggedClient();
        $actor = $this->user('admin@example.com');
        $account = $this->account();
        $this->userManager()->deactivate($account, $actor);

        $client->submit($this->rowButton($client, $account, 'user-activate')->form());
        self::assertResponseRedirects('/admin/comptes');

        $this->logInWithForm($client, $account->getUserIdentifier(), self::PASSWORD);
        self::assertResponseRedirects('/');
    }

    public function testAdminCanNeitherDeactivateThemselvesNorDropTheirAdminStatus(): void
    {
        $client = self::createClient();
        $admin = $this->account(admin: true);
        $this->logInAs($client, $admin->getUserIdentifier());

        $crawler = $client->request('GET', '/admin/comptes');
        self::assertCount(
            0,
            $this->row($crawler, $admin->getUserIdentifier())->filter('[data-test="user-deactivate"]'),
        );
        $client->request('POST', sprintf('/admin/comptes/%d/desactiver', (int) $admin->getId()), [
            '_token' => $this->deactivateToken($crawler),
        ]);
        self::assertResponseRedirects('/admin/comptes');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Vous ne pouvez pas désactiver votre propre compte.');

        $crawler = $client->request('GET', $this->editPath($admin));
        self::assertNotNull($crawler->filter('[data-test="user-admin"]')->attr('disabled'));
        $this->submit($client, ['email' => $admin->getUserIdentifier()], $this->editPath($admin), 'user_edition');
        self::assertResponseRedirects('/admin/comptes');

        $reloaded = $this->user($admin->getUserIdentifier());
        self::assertTrue($reloaded->isActive());
        self::assertTrue($reloaded->isAdmin());
    }

    public function testPermanentAdminCanNeitherBeDeactivatedNorLoseTheAdminStatus(): void
    {
        $client = self::createClient();
        $admin = $this->account(admin: true);
        $permanent = $this->user('admin@example.com');
        $this->logInAs($client, $admin->getUserIdentifier());

        $crawler = $client->request('GET', '/admin/comptes');
        $row = $this->row($crawler, 'admin@example.com');
        self::assertCount(1, $row->filter('[data-test="user-permanent"]'));
        self::assertCount(0, $row->filter('[data-test="user-deactivate"]'));
        $client->request('POST', sprintf('/admin/comptes/%d/desactiver', (int) $permanent->getId()), [
            '_token' => $this->deactivateToken($crawler),
        ]);
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'L\'admin permanent ne peut pas être désactivé.');

        $crawler = $client->request('GET', $this->editPath($permanent));
        self::assertNotNull($crawler->filter('[data-test="user-admin"]')->attr('disabled'));

        self::assertTrue($this->user('admin@example.com')->isActive());
    }

    public function testDeactivationRequiresACsrfToken(): void
    {
        $client = $this->loggedClient();
        $account = $this->account();

        $client->request('POST', sprintf('/admin/comptes/%d/desactiver', (int) $account->getId()), [
            '_token' => 'invalide',
        ]);

        self::assertTrue($this->user($account->getUserIdentifier())->isActive());
    }
}
