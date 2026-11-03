<?php

declare(strict_types=1);

namespace App\Tests\Controller;

final class LinkControllerTest extends LinkWebTestCase
{
    public function testAnonymousUserIsRedirectedToLogin(): void
    {
        $client = self::createClient();

        $client->request('GET', '/liens/ajouter');

        self::assertResponseRedirects('/login');
    }

    public function testAddLinkWithExistingCategoryShowsItFirstOnHome(): void
    {
        $client = $this->loggedClient();
        $url = 'https://example.com/' . uniqid('nominal-', more_entropy: false);

        $this->submit($client, [
            'url' => $url,
            'categories' => [$this->categoryId('Symfony')],
            'context' => 'Pour la recette mobile.',
        ]);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'Lien ajouté.');
        self::assertSelectorExists(sprintf('%s [data-test="link-open"][title="Ouvrir %s"]', self::FIRST_CARD, $url));
        self::assertSelectorTextContains(self::FIRST_CARD . ' [data-test="link-poster"]', 'admin');
        self::assertSelectorTextContains(self::FIRST_CARD . ' [data-test="link-category"]', 'Symfony');
        self::assertSelectorTextContains(self::FIRST_CARD . ' [data-test="link-context"]', 'Pour la recette mobile.');
    }

    public function testThreeCategoriesAreKept(): void
    {
        $client = $this->loggedClient();

        $this->submit($client, [
            'url' => 'https://example.com/' . uniqid('three-'),
            'categories' => [
                $this->categoryId('Symfony'),
                $this->categoryId('Agents'),
                $this->categoryId('Architecture'),
            ],
        ]);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorCount(3, self::FIRST_CARD . ' [data-test="link-category"]');
    }

    public function testLinkWithoutCategoryIsRefused(): void
    {
        $client = $this->loggedClient();

        $this->submit($client, ['url' => 'https://example.com/no-category']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="link-categories"]', 'Choisissez au moins une catégorie');
    }

    public function testLinkWithFourCategoriesIsRefused(): void
    {
        $client = $this->loggedClient();

        $this->submit($client, [
            'url' => 'https://example.com/four',
            'categories' => [
                $this->categoryId('Symfony'),
                $this->categoryId('Agents'),
                $this->categoryId('Architecture'),
                $this->createCategory('Une quatrième ' . uniqid()),
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="link-categories"]', 'au plus trois catégories');
    }

    public function testInvalidUrlIsRefusedInFrench(): void
    {
        $client = $this->loggedClient();

        $this->submit($client, [
            'url' => 'ftp://example.com/file',
            'categories' => [$this->categoryId('Symfony')],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="link-form"]', 'Cette valeur n\'est pas une URL valide.');
    }
}
