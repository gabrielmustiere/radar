<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Enum\Type\Team;
use App\Service\SystemUserProvider;

final class AdminUserControllerTest extends AdminUserWebTestCase
{
    public function testMemberWhoIsNotAdminSeesNeitherTheMenuNorThePages(): void
    {
        $client = self::createClient();
        $this->logInAs($client, 'marie@example.com');

        $client->request('GET', '/');
        self::assertSelectorNotExists('[data-test="nav-users"]');

        foreach (['/admin/comptes', '/admin/comptes/nouveau'] as $path) {
            $client->request('GET', $path);
            self::assertResponseStatusCodeSame(403);
        }
    }

    public function testAdminSeesTheAccountsEntry(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/');

        self::assertSelectorExists('[data-test="nav-users"]');
    }

    public function testCreatedAccountLogsInWithItsPassword(): void
    {
        $client = $this->loggedClient();
        $email = uniqid('qa-') . '@example.com';

        $this->submit(
            $client,
            ['email' => strtoupper($email), 'team' => Team::Qa->value, 'password' => 's3cret-Passw0rd'],
            '/admin/comptes/nouveau',
            'user_creation',
        );
        self::assertResponseRedirects('/admin/comptes');
        $client->followRedirect();
        $row = $this->row($client->getCrawler(), $email);
        self::assertSame('QA', $row->filter('[data-test="user-team"]')->text());
        self::assertSame('Actif', $row->filter('[data-test="user-state"]')->text());

        $this->logInWithForm($client, $email, 's3cret-Passw0rd');
        self::assertResponseRedirects('/');
    }

    public function testEmailAlreadyUsedIsRefusedWhateverItsCase(): void
    {
        $client = $this->loggedClient();

        $this->submit(
            $client,
            ['email' => 'MARIE@example.com', 'team' => Team::Qa->value, 'password' => 's3cret-Passw0rd'],
            '/admin/comptes/nouveau',
            'user_creation',
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="user-form"]', 'Cet email a déjà un compte.');
    }

    public function testTeamIsRequiredAtCreation(): void
    {
        $client = $this->loggedClient();

        $this->submit(
            $client,
            ['email' => uniqid('sans-equipe-') . '@example.com', 'password' => 's3cret-Passw0rd'],
            '/admin/comptes/nouveau',
            'user_creation',
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="user-form"]', 'Choisissez une équipe.');
    }

    public function testChangedTeamAppearsInTheList(): void
    {
        $client = $this->loggedClient();
        $account = $this->account(Team::Qa);

        $this->submit(
            $client,
            ['email' => $account->getUserIdentifier(), 'team' => Team::Product->value],
            $this->editPath($account),
            'user_edition',
        );
        self::assertResponseRedirects('/admin/comptes');

        $crawler = $client->request('GET', '/admin/comptes');
        self::assertSame(
            'Produit',
            $this->row($crawler, $account->getUserIdentifier())->filter('[data-test="user-team"]')->text(),
        );
    }

    public function testEmailOfAnotherAccountIsRefusedAtEdition(): void
    {
        $client = $this->loggedClient();
        $account = $this->account();

        $this->submit($client, ['email' => 'Karim@Example.com'], $this->editPath($account), 'user_edition');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="user-form"]', 'Cet email a déjà un compte.');
    }

    public function testNewPasswordReplacesTheOldOne(): void
    {
        $client = $this->loggedClient();
        $account = $this->account();

        $this->submit($client, ['password' => 'nouveau-mot-de-passe'], $this->editPath($account), 'user_password');
        self::assertResponseRedirects('/admin/comptes');

        $this->logInWithForm($client, $account->getUserIdentifier(), self::PASSWORD);
        self::assertResponseRedirects('/login');

        $this->logInWithForm($client, $account->getUserIdentifier(), 'nouveau-mot-de-passe');
        self::assertResponseRedirects('/');
    }

    public function testTooShortPasswordIsRefused(): void
    {
        $client = $this->loggedClient();
        $account = $this->account();

        $this->submit($client, ['password' => 'court'], $this->editPath($account), 'user_password');

        self::assertResponseStatusCodeSame(422);
    }

    public function testSystemAccountIsNeitherListedNorEditable(): void
    {
        $client = $this->loggedClient();
        $system = $this->user(SystemUserProvider::EMAIL);

        $client->request('GET', '/admin/comptes');
        self::assertSelectorTextNotContains('[data-test="user-list"]', SystemUserProvider::EMAIL);

        $client->request('GET', $this->editPath($system));
        self::assertResponseStatusCodeSame(404);
    }
}
