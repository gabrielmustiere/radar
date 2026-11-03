<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use Playwright\Locator\LocatorInterface;

final class AddLinkTest extends E2ETestCase
{
    public function testMemberAddsLinkFromTopbarModal(): void
    {
        $this->logIn();
        $url = $this->freeRealLink();

        $modal = $this->openModal();
        $this->expect($modal->locator('h1'))->toContainText('Ajouter un lien');

        $modal->locator('[data-test="link-url"]')->fill($url);
        $modal->locator('[data-test="link-categories"] > div > label')->first()->click();
        $modal->locator('[data-test="link-context"]')->fill('Repéré en E2E.');
        $modal->locator('[data-test="link-submit"]')->click();

        $this->expect($modal)->toBeHidden();
        $this->expect($this->page)->toHaveURL(self::baseUrl() . '/');
        $this->expect($this->page->locator('[role="alert"]'))->toContainText('Lien ajouté.');
        $first = $this->page->locator('[data-test="link-card"]')->first();
        $this->expect($first->locator('[data-test="link-context"]'))->toHaveText('Repéré en E2E.');
        $this->expect($first->locator('[data-test="link-open"]'))->toHaveAttribute('title', 'Ouvrir ' . $url);
    }

    public function testDuplicateIsShownAsSoonAsTheUrlIsTyped(): void
    {
        $this->logIn();
        $url = $this->freeRealLink();
        $modal = $this->openModal();
        $modal->locator('[data-test="link-url"]')->fill($url);
        $modal->locator('[data-test="link-categories"] > div > label')->first()->click();
        $modal->locator('[data-test="link-submit"]')->click();
        $this->expect($modal)->toBeHidden();

        $modal = $this->openModal();
        // Même lien une fois normalisé (www ajouté ou retiré)
        $modal
            ->locator('[data-test="link-url"]')
            ->fill(
                str_contains($url, '://www.')
                    ? str_replace(search: '://www.', replace: '://', subject: $url)
                    : str_replace(search: '://', replace: '://www.', subject: $url),
            );

        $this->expect($modal->locator('[data-test="link-duplicate"]'))->toContainText('Ce lien est déjà dans Radar');
        $this->expect($modal->locator('[data-test="link-submit"]'))->toBeDisabled();
    }

    public function testFourthCategoryIsLockedOnceThreeAreChecked(): void
    {
        $this->logIn();
        $modal = $this->openModal();

        $labels = $modal->locator('[data-test="link-categories"] > div > label');
        $labels->nth(0)->click();
        $labels->nth(1)->click();
        $labels->nth(2)->click();

        $this->expect($modal->locator('[data-test="link-categories"] input[type="checkbox"]')->nth(3))->toBeDisabled();
    }

    public function testFormStartsWithTheAddressThenCategoriesThenContext(): void
    {
        $this->logIn();
        $modal = $this->openModal();

        $this->expect($modal->locator('[data-test="link-url"]'))->toBeFocused();
        self::assertSame(
            ['link-url', 'link-categories', 'link-context'],
            $this->page->evaluate(
                "[...document.querySelectorAll('[data-test=\"link-modal\"] [data-test=\"link-context\"], [data-test=\"link-modal\"] [data-test=\"link-categories\"], [data-test=\"link-modal\"] [data-test=\"link-url\"]')].map(e => e.dataset.test)",
            ),
        );
    }

    public function testTypedAddressShowsItsDetectedType(): void
    {
        $this->logIn();
        $modal = $this->openModal();

        $modal->locator('[data-test="link-url"]')->fill('https://github.com/symfony/ux');
        $this->expect($modal->locator('[data-test="link-detected-type"]'))->toHaveText('Dépôt GitHub');

        $modal->locator('[data-test="link-url"]')->fill('https://www.youtube.com/watch?v=qjSAMOkrdIQ');
        $this->expect($modal->locator('[data-test="link-detected-type"]'))->toHaveText('Vidéo YouTube');
    }

    public function testEnterCreatesAndChecksACategoryWithoutSubmitting(): void
    {
        $this->logIn();
        $modal = $this->openModal();
        // Nom stable : le premier passage la crée, les suivants la retrouvent (aucune catégorie ne se supprime).
        $name = 'e2e créée à l\'entrée';

        $query = $modal->locator('[data-test="category-query"]');
        $query->fill($name);
        $query->press('Enter');

        $pill = $modal->locator(sprintf('[data-test="category-pill"]:has-text("%s")', ucfirst($name)));
        $this->expect($pill->locator('input'))->toBeChecked();
        $this->expect($query)->toHaveValue('');
        $this->expect($modal)->toBeVisible();
        $this->expect($modal->locator('[data-test="link-url"]'))->toHaveValue('');
    }

    public function testPlusButtonCreatesAndChecksACategoryWithoutSubmitting(): void
    {
        $this->logIn();
        $modal = $this->openModal();
        // Nom stable : le premier passage la crée, les suivants la retrouvent (aucune catégorie ne se supprime).
        $name = 'e2e créée au bouton plus';

        $query = $modal->locator('[data-test="category-query"]');
        $query->fill($name);
        $modal->locator('[data-test="category-create"]')->click();

        $pill = $modal->locator(sprintf('[data-test="category-pill"]:has-text("%s")', ucfirst($name)));
        $this->expect($pill->locator('input'))->toBeChecked();
        $this->expect($query)->toHaveValue('');
        $this->expect($modal)->toBeVisible();
    }

    public function testDuplicateIsCompletedWithTheCheckedCategories(): void
    {
        $this->logIn();
        $url = $this->freeRealLink();
        $modal = $this->openModal();
        $labels = $modal->locator('[data-test="link-categories"] > div > label');
        $labels->nth(0)->click();
        $modal->locator('[data-test="link-url"]')->fill($url);
        $modal->locator('[data-test="link-submit"]')->click();
        $this->expect($modal)->toBeHidden();

        $modal = $this->openModal();
        $modal->locator('[data-test="link-categories"] > div > label')->nth(1)->click();
        $modal->locator('[data-test="link-url"]')->fill($url);
        $modal->locator('[data-test="link-duplicate-complete"]')->click();

        $this->expect($this->page->locator('[role="alert"]'))->toContainText('Catégories ajoutées.');
        self::assertMatchesRegularExpression('#/liens/\\d+$#', $this->page->url());
        $this->expect($this->page->locator('[data-test="link-card"] [data-test="link-category"]'))->toHaveCount(2);
    }

    public function testCancelClosesTheModalAndStartsAFreshFormNextTime(): void
    {
        $this->logIn();
        $modal = $this->openModal();
        $modal->locator('[data-test="link-url"]')->fill('https://brouillon.example');

        $modal->locator('[data-test="link-cancel"]')->click();
        $this->expect($modal)->toBeHidden();

        $modal = $this->openModal();
        $this->expect($modal->locator('[data-test="link-url"]'))->toHaveValue('');
    }

    private function openModal(): LocatorInterface
    {
        $this->page->click('[data-test="add-link"]');
        $modal = $this->page->locator('[data-test="link-modal"]');
        $this->expect($modal->locator('[data-test="link-url"]'))->toBeVisible();

        return $modal;
    }
}
