<?php

declare(strict_types=1);

namespace App\Tests\E2E;

final class LoginTest extends E2ETestCase
{
    public function testLoginFlow(): void
    {
        $this->page->goto('/login');

        $this->expect($this->page->locator('h1'))->toContainText('Se connecter');

        $this->page->locator('input[name="_username"]')->fill('admin@example.com');
        $this->page->locator('input[name="_password"]')->fill('password');
        $this->page->click('button[type="submit"]');

        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/');
        $this->expect($this->page->locator('body'))->toContainText('Tableau de bord');
    }
}
