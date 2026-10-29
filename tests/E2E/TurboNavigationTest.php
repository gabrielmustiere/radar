<?php

declare(strict_types=1);

namespace App\Tests\E2E;

// Flowbite doit rester fonctionnel après une navigation Turbo (ici la redirection qui suit la connexion).
final class TurboNavigationTest extends E2ETestCase
{
    public function testFlowbiteDropdownWorksAfterATurboNavigation(): void
    {
        $this->logIn();

        $this->page->click('[data-test="user-menu-toggle"]');
        $this->expect($this->page->locator('[data-test="logout-link"]'))->toBeVisible();
    }
}
