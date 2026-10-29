<?php

declare(strict_types=1);

namespace App\Tests\E2E;

final class AddLinkTest extends E2ETestCase
{
    public function testMemberAddsLinkFromTopbar(): void
    {
        $url = 'https://example.com/e2e-' . uniqid();
        $this->logIn();

        $this->page->click('[data-test="add-link"]');
        $this->expect($this->page->locator('h1'))->toContainText('Ajouter un lien');

        $this->page->locator('[data-test="link-url"]')->fill($url);
        $this->page->locator('[data-test="link-categories"] label')->first()->click();
        $this->page->locator('[data-test="link-context"]')->fill('Repéré en E2E.');
        $this->page->click('[data-test="link-submit"]');

        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/');
        $first = $this->page->locator('[data-test="link-card"]')->first();
        $this->expect($first->locator('[data-test="link-context"]'))->toHaveText('Repéré en E2E.');
        $this->expect($first->locator('[data-test="link-source"]'))->toContainText('example.com');
    }
}
