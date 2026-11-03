<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;

/**
 * L'admin et le nouveau membre naviguent chacun dans leur propre contexte : deux sessions ouvertes en parallèle.
 * Le compte créé reste désactivé en base : son email est unique à chaque passage.
 */
final class AdminUserTest extends E2ETestCase
{
    private const string PASSWORD = 's3cret-Passw0rd';

    public function testAdminCreatesAQaAccountThenDeactivatesIt(): void
    {
        $email = uniqid('e2e-qa-') . '@example.com';
        $this->logIn();
        $this->page->click('[data-test="user-menu-toggle"]');
        $this->page->locator('[data-test="nav-users"]')->click();
        $this->page->locator('[data-test="user-new"]')->click();
        $this->page->locator('[data-test="user-email"]')->fill($email);
        $this->page->locator('[data-test="user-team"]')->selectOption('qa');
        $this->page->locator('[data-test="user-password"]')->fill(self::PASSWORD);
        $this->page->locator('[data-test="user-submit"]')->click();

        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/admin/comptes');
        $this->expect($this->row($this->page, $email)->locator('[data-test="user-team"]'))->toHaveText('QA');

        $member = $this->browser->newContext(['baseURL' => self::baseUrl(), 'ignoreHTTPSErrors' => true])->newPage();
        $member->goto('/login');
        $member->locator('input[name="_username"]')->fill($email);
        $member->locator('input[name="_password"]')->fill(self::PASSWORD);
        $member->click('button[type="submit"]');
        $this->expect($member)->toHaveURL(self::baseUrl() . '/');

        $this->row($this->page, $email)->locator('[data-test="user-deactivate"]')->click();
        $this->expect($this->row($this->page, $email)->locator('[data-test="user-state"]'))->toContainText('désactivé');

        $member->goto('/');
        $this->expect($member)->toHaveURL(self::baseUrl() . '/login');
        $member->locator('input[name="_username"]')->fill($email);
        $member->locator('input[name="_password"]')->fill(self::PASSWORD);
        $member->click('button[type="submit"]');
        $this->expect($member->locator('[role="alert"]'))->toContainText('Ce compte est désactivé.');
    }

    private function row(PageInterface $page, string $email): LocatorInterface
    {
        // `filter()` et non l'option `hasText` de `locator()` : Playwright PHP perd cette option au chaînage suivant.
        return $page->locator('[data-test="user-row"]')->filter(['hasText' => $email]);
    }
}
