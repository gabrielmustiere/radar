<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Enum\Type\Team;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

final class AdminCategoryControllerTest extends SourceWebTestCase
{
    public function testMemberWhoIsNotAdminSeesNeitherTheMenuNorThePage(): void
    {
        $client = self::createClient();
        $this->logInAs($client, 'marie@example.com');

        $client->request('GET', '/');
        self::assertSelectorNotExists('[data-test="nav-categories"]');

        $client->request('GET', '/admin/categories');
        self::assertResponseStatusCodeSame(403);
    }

    public function testEveryCategoryIsListedWithItsLinkCountAndTeam(): void
    {
        $client = $this->loggedClient();
        $name = 'Sans lien ' . uniqid();
        $this->createCategory($name);
        $this->category($name)->setTeam(Team::Management);
        $this->entityManager()->flush();

        $crawler = $client->request('GET', '/admin/categories');
        self::assertSelectorExists('[data-test="nav-categories"]');

        $row = $this->row($crawler, $name);
        self::assertSame('0', $row->filter('[data-test="category-link-count"]')->text());
        self::assertSame(
            Team::Management->value,
            $row->filter('[data-test="category-team"] option[selected]')->attr('value'),
        );
        self::assertNotSame('0', $this->row($crawler, 'Symfony')->filter('[data-test="category-link-count"]')->text());
    }

    public function testAdminChangesThenRemovesTheTeamOfACategory(): void
    {
        $client = $this->loggedClient();
        $name = 'Équipe changée ' . uniqid();
        $this->createCategory($name);

        $client->submit($this->teamForm($client, $name), ['team' => Team::Product->value]);
        self::assertResponseRedirects('/admin/categories');
        self::assertSame(Team::Product, $this->category($name)->getTeam());

        $client->submit($this->teamForm($client, $name), ['team' => '']);
        self::assertResponseRedirects('/admin/categories');
        self::assertNull($this->category($name)->getTeam());
    }

    public function testUnknownTeamIsRefused(): void
    {
        $client = $this->loggedClient();
        $name = 'Équipe inconnue ' . uniqid();
        $this->createCategory($name);
        $form = $this->teamForm($client, $name);

        $client->request('POST', $form->getUri(), [...$form->getValues(), 'team' => 'marketing']);

        self::assertResponseStatusCodeSame(400);
        self::assertNull($this->category($name)->getTeam());
    }

    public function testTeamChangeRequiresACsrfToken(): void
    {
        $client = $this->loggedClient();
        $name = 'Sans jeton ' . uniqid();
        $this->createCategory($name);

        $client->request('POST', sprintf('/admin/categories/%d/equipe', (int) $this->category($name)->getId()), [
            '_token' => 'invalide',
            'team' => Team::Qa->value,
        ]);

        self::assertNull($this->category($name)->getTeam());
    }

    private function teamForm(KernelBrowser $client, string $name): Form
    {
        return $this
            ->row($client->request('GET', '/admin/categories'), $name)
            ->filter('[data-test="category-team-submit"]')
            ->form();
    }

    private function row(Crawler $crawler, string $name): Crawler
    {
        $row = $crawler
            ->filter('[data-test="category-row"]')
            ->reduce(static fn(Crawler $node): bool => $node->filter('[data-test="category-name"]')->text() === $name);
        self::assertCount(1, $row, sprintf('Catégorie « %s » absente de la liste.', $name));

        return $row;
    }

    private function category(string $name): Category
    {
        $this->entityManager()->clear();
        $repository = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $repository);
        $category = $repository->findOneBy(['name' => $name]);
        self::assertInstanceOf(Category::class, $category);

        return $category;
    }
}
