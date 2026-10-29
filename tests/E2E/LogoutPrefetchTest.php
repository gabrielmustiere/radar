<?php

declare(strict_types=1);

namespace App\Tests\E2E;

// Turbo 8 précharge les liens au survol : survoler « Déconnexion » ne doit pas déconnecter.
final class LogoutPrefetchTest extends E2ETestCase
{
    public function testHoveringTheLogoutLinkKeepsTheUserLoggedIn(): void
    {
        $this->logIn();

        $this->page->click('[data-test="user-menu-toggle"]');
        $this->page->locator('[data-test="logout-link"]')->hover();
        usleep(500_000);

        $this->page->reload();
        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/');
        $this->expect($this->page->locator('body'))->toContainText('Tableau de bord');
    }
}
