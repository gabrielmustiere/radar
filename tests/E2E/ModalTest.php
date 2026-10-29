<?php

declare(strict_types=1);

namespace App\Tests\E2E;

// Le contrôleur Stimulus du modal ne doit pas entrer en conflit avec le JS de Flowbite (attribut data-modal-target).
final class ModalTest extends E2ETestCase
{
    public function testModalOpensAndClosesWithoutJsErrors(): void
    {
        // Playwright PHP n'expose pas les erreurs non capturées (pageerror) : on les collecte côté page.
        $this->page->addInitScript(<<<'JS'
            window.__pageErrors = [];
            window.addEventListener('error', (event) => window.__pageErrors.push(event.message));
            window.addEventListener('unhandledrejection', (event) => window.__pageErrors.push(String(event.reason)));
            JS);

        $this->logIn();

        $this->page->goto('/design-system');
        $this->page->click('[data-test="modal-trigger"]');
        $this->expect($this->page->locator('#modal-modal-confirm'))->toBeVisible();
        $this->page->click('[data-test="modal-cancel"]');
        $this->expect($this->page->locator('#modal-modal-confirm'))->toBeHidden();

        self::assertSame([], $this->page->evaluate('window.__pageErrors'));

        $consoleErrors = [];
        foreach ($this->page->consoleMessages(['filter' => 'all']) as $message) {
            if ('error' !== $message->type() || str_starts_with($message->text(), 'Failed to load resource')) {
                continue;
            }

            $consoleErrors[] = $message->text();
        }

        self::assertSame([], $consoleErrors);
    }
}
