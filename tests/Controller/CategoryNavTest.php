<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Enum\Type\Team;
use App\Repository\CategoryRepository;
use App\Service\CategoryManager;
use App\Service\CategoryResolver;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sidebar par équipe. Fixtures : `admin@example.com` en Développement, `marie@example.com` en Produit. Chaque test
 * crée ses catégories, avec un lien pour qu'elles apparaissent dans la sidebar.
 */
final class CategoryNavTest extends SourceWebTestCase
{
    public function testCategoryOfAProductMemberIsInTheTeamSectionOfAnotherProductMemberNamedInItsTitle(): void
    {
        $client = $this->loggedClient();
        $prefix = 'Produit ' . uniqid() . ' ';
        $creator = $this->account(Team::Product);
        foreach (['zèbre', 'Éclair'] as $name) {
            $this->categoryWithLink($client, $prefix . $name, $creator);
        }

        $this->logInAs($client, 'marie@example.com');
        $crawler = $client->request('GET', '/');

        self::assertSame([$prefix . 'Éclair', $prefix . 'zèbre'], $this->names($crawler, 'category-nav-mine', $prefix));
        self::assertSame([], $this->names($crawler, 'category-nav-others', $prefix));
        self::assertSelectorTextContains('[data-test="category-nav-mine-team"]', 'Produit');
        self::assertCount(0, $this->item($crawler, $prefix . 'zèbre')->filter('[data-test="category-nav-team"]'));
    }

    public function testCategoryOfAnotherTeamIsInTheOtherSectionWithItsBadge(): void
    {
        $client = $this->loggedClient();
        $name = 'Autre équipe ' . uniqid();
        $this->categoryWithLink($client, $name, $this->account(Team::Product));

        $crawler = $client->request('GET', '/');

        self::assertSame([$name], $this->names($crawler, 'category-nav-others', $name));
        self::assertSame([], $this->names($crawler, 'category-nav-mine', $name));
        self::assertSame('Produit', $this->item($crawler, $name)->filter('[data-test="category-nav-team"]')->text());
    }

    public function testCategoryWithoutTeamHasNoBadge(): void
    {
        $client = $this->loggedClient();
        $name = 'Sans badge ' . uniqid();
        $this->categoryWithLink($client, $name, null);

        $crawler = $client->request('GET', '/');

        self::assertCount(0, $this->item($crawler, $name)->filter('[data-test="category-nav-team"]'));
    }

    public function testChangedTeamMovesTheCategoryToTheTeamSection(): void
    {
        $client = $this->loggedClient();
        $name = 'Déplacée ' . uniqid();
        $category = $this->categoryWithLink($client, $name, $this->account(Team::Qa));
        $this->logInAs($client, 'marie@example.com');
        self::assertSame([$name], $this->names($client->request('GET', '/'), 'category-nav-others', $name));

        $manager = self::getContainer()->get(CategoryManager::class);
        self::assertInstanceOf(CategoryManager::class, $manager);
        $repository = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $repository);
        $manager->changeTeam(
            $repository->find((int) $category->getId()) ?? self::fail('Catégorie absente.'),
            Team::Product,
        );

        self::assertSame([$name], $this->names($client->request('GET', '/'), 'category-nav-mine', $name));
    }

    public function testMemberWithoutTeamSeesASingleList(): void
    {
        $client = self::createClient();
        $this->logInAs($client, $this->account(null)->getUserIdentifier());

        $client->request('GET', '/');

        self::assertSelectorNotExists('[data-test="category-nav-mine"]');
        self::assertSelectorTextContains('[data-test="category-nav-others"] p', 'Catégories');
    }

    public function testTeamSectionIsHiddenWhenNoCategoryOfTheTeamHasALink(): void
    {
        $client = self::createClient();
        $this->createCategory('Management sans lien ' . uniqid());
        $this->logInAs($client, $this->account(Team::Management)->getUserIdentifier());

        $client->request('GET', '/');

        self::assertSelectorNotExists('[data-test="category-nav-mine"]');
        self::assertSelectorTextContains('[data-test="category-nav-others"] p', 'Catégories');
    }

    private function account(?Team $team): User
    {
        $manager = self::getContainer()->get(UserManager::class);
        self::assertInstanceOf(UserManager::class, $manager);

        return $manager->create(uniqid('nav-') . '@example.com', 'mot-de-passe', $team, admin: false);
    }

    private function categoryWithLink(KernelBrowser $client, string $name, ?User $creator): Category
    {
        $resolver = self::getContainer()->get(CategoryResolver::class);
        self::assertInstanceOf(CategoryResolver::class, $resolver);
        $category = $resolver->resolve($name, $creator);

        $this->logInAs($client, 'admin@example.com');
        $this->submit($client, [
            'url' => 'https://example.com/' . uniqid('nav-'),
            'categories' => [(int) $category->getId()],
        ]);
        self::assertResponseRedirects('/');

        return $category;
    }

    /** @return list<string> noms de la section, restreints à ceux qui commencent par `$prefix` */
    private function names(Crawler $crawler, string $section, string $prefix): array
    {
        return array_values(array_filter(
            $crawler
                ->filter(sprintf('[data-test="%s"] [data-test="category-nav-item"] span:first-of-type', $section))
                ->each(static fn(Crawler $node): string => $node->text()),
            static fn(string $name): bool => str_starts_with($name, $prefix),
        ));
    }

    private function item(Crawler $crawler, string $name): Crawler
    {
        $item = $crawler
            ->filter('[data-test="category-nav-item"]')
            ->reduce(static fn(Crawler $node): bool => $node->filter('span')->first()->text() === $name);
        self::assertCount(1, $item);

        return $item;
    }
}
