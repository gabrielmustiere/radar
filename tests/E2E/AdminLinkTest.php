<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use Playwright\Locator\LocatorInterface;

final class AdminLinkTest extends E2ETestCase
{
    private const string TRIGGER = '[data-test="link-admin-actions"]:visible';

    public function testAdminRemovesALinkFromTheModalOfItsCard(): void
    {
        $this->logIn();
        $url = 'https://example.com/' . uniqid('e2e-suppression-');
        $modal = $this->openModal($this->page->locator('[data-test="add-link"]'));
        $modal->locator('[data-test="link-url"]')->fill($url);
        $modal->locator('[data-test="link-categories"] > div > label')->first()->click();
        $modal->locator('[data-test="link-submit"]')->click();
        $this->expect($modal)->toBeHidden();
        $card = $this->page->locator('[data-test="link-card"]')->first();
        $this->expect($card->locator('[data-test="link-open"]'))->toHaveAttribute('title', 'Ouvrir ' . $url);

        $modal = $this->openModal($card->locator(self::TRIGGER));
        $this->expect($modal->locator('h1'))->toContainText('Administrer ce lien');
        $modal->locator('[data-test="admin-link-remove"]')->click();

        $this->expect($modal)->toBeHidden();
        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/');
        $this->expect($this->page->locator('[role="alert"]'))->toContainText('supprimé.');
        $this->page->goto('/?q=' . rawurlencode($url));
        $this->expect($this->page->locator('[data-test="search-empty"]'))->toBeVisible();
    }

    public function testAdminReannouncesALinkFromThePhoneLayout(): void
    {
        $this->page->setViewportSize(390, 844);
        $this->logIn();

        $modal = $this->openModal($this->readyCard()->locator(self::TRIGGER));
        $this->expect($modal->locator('[data-test="admin-link-announced"]'))->toBeVisible();
        $modal->locator('[data-test="admin-link-reannounce"]')->click();

        $this->expect($modal)->toBeHidden();
        $this->expect($this->page->locator('[role="alert"]'))->toContainText('mise en file');
    }

    public function testClosingTheModalLeavesTheLinkInPlace(): void
    {
        $this->logIn();
        $card = $this->readyCard();
        $title = $card->locator('[data-test="link-title"]')->innerText();

        $modal = $this->openModal($card->locator(self::TRIGGER));
        $this->expect($modal->locator('[data-test="admin-link-title"]'))->toBeVisible();
        $this->page->locator('[data-test="link-modal-close"]')->click();

        $this->expect($modal)->toBeHidden();
        $this->expect($this->readyCard()->locator('[data-test="link-title"]'))->toHaveText($title);
    }

    private function readyCard(): LocatorInterface
    {
        return $this->page->locator('[data-test="link-card"]:not([data-preview-status="pending"])')->first();
    }

    private function openModal(LocatorInterface $trigger): LocatorInterface
    {
        $trigger->click();
        $modal = $this->page->locator('[data-test="link-modal"]');
        $this->expect($modal)->toBeVisible();

        return $modal;
    }
}
