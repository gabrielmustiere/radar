<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LinkControllerTest extends WebTestCase
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
            'categories' => [$this->categoryId('Tester une app mobile')],
            'context' => 'Pour la recette mobile.',
        ]);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'Lien ajouté.');
        self::assertSelectorTextContains('[data-test="link-item"]:first-child', $url);
        self::assertSelectorTextContains('[data-test="link-item"]:first-child [data-test="link-poster"]', 'admin');
        self::assertSelectorTextContains(
            '[data-test="link-item"]:first-child [data-test="link-category"]',
            'Tester une app mobile',
        );
        self::assertSelectorTextContains(
            '[data-test="link-item"]:first-child [data-test="link-context"]',
            'Pour la recette mobile.',
        );
    }

    public function testAddLinkCreatesNewCategoryOfferedOnNextAdd(): void
    {
        $client = $this->loggedClient();
        $name = 'Mesurer la perf ' . uniqid();

        $this->submit($client, ['url' => 'https://example.com/perf', 'newCategory' => $name]);

        self::assertResponseRedirects('/');
        $client->request('GET', '/liens/ajouter');
        self::assertSelectorTextContains('[data-test="link-categories"]', $name);
    }

    public function testCheckedAndCreatedCategoriesAreBothKept(): void
    {
        $client = $this->loggedClient();
        $name = 'Automatiser la recette ' . uniqid();

        $this->submit($client, [
            'url' => 'https://example.com/two-categories',
            'categories' => [$this->categoryId('Concevoir une interface')],
            'newCategory' => $name,
        ]);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorCount(2, '[data-test="link-item"]:first-child [data-test="link-category"]');
        self::assertSelectorTextContains('[data-test="link-item"]:first-child', $name);
    }

    public function testExistingCategoryNameIsReusedWhateverTheCase(): void
    {
        $client = $this->loggedClient();

        $this->submit($client, ['url' => 'https://example.com/reuse', 'newCategory' => 'CHOISIR UNE LIBRAIRIE php']);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorTextContains(
            '[data-test="link-item"]:first-child [data-test="link-category"]',
            'Choisir une librairie PHP',
        );
        $repository = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $repository);
        $matching = array_filter(
            $repository->findAllOrderedByName(),
            static fn(Category $category): bool => 'choisir une librairie php' === mb_strtolower($category->getName()),
        );
        self::assertCount(1, $matching);
    }

    public function testLinkWithoutCategoryIsRefused(): void
    {
        $client = $this->loggedClient();

        $this->submit($client, ['url' => 'https://example.com/no-category']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="link-categories"]', 'Choisissez au moins une catégorie');
    }

    public function testLinkWithThreeCategoriesIsRefused(): void
    {
        $client = $this->loggedClient();

        $this->submit($client, [
            'url' => 'https://example.com/three',
            'categories' => [$this->categoryId('Tester une app mobile'), $this->categoryId('Concevoir une interface')],
            'newCategory' => 'Une troisième ' . uniqid(),
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="link-categories"]', 'au plus deux catégories');
    }

    public function testTypingTheNameOfACheckedCategoryDoesNotCountTwice(): void
    {
        $client = $this->loggedClient();

        $this->submit($client, [
            'url' => 'https://example.com/same-twice',
            'categories' => [$this->categoryId('Tester une app mobile'), $this->categoryId('Concevoir une interface')],
            'newCategory' => 'tester une app mobile',
        ]);

        self::assertResponseRedirects('/');
    }

    public function testInvalidUrlIsRefusedInFrench(): void
    {
        $client = $this->loggedClient();

        $this->submit($client, [
            'url' => 'ftp://example.com/file',
            'categories' => [$this->categoryId('Tester une app mobile')],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="link-form"]', 'Cette valeur n\'est pas une URL valide.');
    }

    private function loggedClient(): KernelBrowser
    {
        $client = self::createClient();
        $repository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);
        $user = $repository->findOneBy(['email' => 'admin@example.com']);
        self::assertNotNull($user);
        $client->loginUser($user);

        return $client;
    }

    /** @param array<string, mixed> $values */
    private function submit(KernelBrowser $client, array $values): void
    {
        $crawler = $client->request('GET', '/liens/ajouter');
        $token = $crawler->filter('input[name="link[_token]"]')->attr('value');

        $client->request('POST', '/liens/ajouter', ['link' => [...$values, '_token' => $token]]);
    }

    private function categoryId(string $name): int
    {
        $repository = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $repository);
        foreach ($repository->findAllOrderedByName() as $category) {
            if ($category->getName() === $name) {
                return (int) $category->getId();
            }
        }

        self::fail(sprintf('Catégorie « %s » absente des fixtures.', $name));
    }
}
