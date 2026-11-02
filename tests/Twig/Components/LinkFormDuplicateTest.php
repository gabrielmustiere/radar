<?php

declare(strict_types=1);

namespace App\Tests\Twig\Components;

use App\Entity\Category;
use App\Entity\Link;
use App\Enum\Type\SourceType;
use App\Repository\LinkRepository;
use App\Service\LinkManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

final class LinkFormDuplicateTest extends LinkFormTestCase
{
    public function testDuplicateOffersTheCheckedCategoriesItLacksAndCompletesIt(): void
    {
        $link = $this->existingLink(['Symfony']);
        $component = $this->component()->set('link.url', $link->getUrl())->set('link.categories', [
            $this->category('Symfony'),
            $this->category('Agents'),
        ]);

        $completion = $component->render()->crawler()->filter('[data-test="link-duplicate-completion"]');
        self::assertSame(
            ['Agents'],
            $completion
                ->filter('[data-test="link-duplicate-candidate"]')
                ->each(static fn(Crawler $node): string => $node->text()),
        );
        self::assertStringContainsString("Votre contexte n'est pas repris", $completion->text());

        $component->call('completeDuplicate');

        self::assertResponseRedirects('/liens/' . (int) $link->getId());
        $repository = self::getContainer()->get(LinkRepository::class);
        self::assertInstanceOf(LinkRepository::class, $repository);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();
        $completed = $repository->find((int) $link->getId());
        self::assertNotNull($completed);
        self::assertSame(
            ['Agents', 'Symfony'],
            array_values(
                $completed
                    ->getCategories()
                    ->map(static fn(Category $category): string => $category->getName())
                    ->toArray(),
            ),
        );
    }

    public function testDuplicateAsksToUncheckWhenTheCategoriesDoNotFit(): void
    {
        $link = $this->existingLink(['Symfony', 'Agents']);
        $component = $this->component()->set('link.url', $link->getUrl())->set('link.categories', [
            $this->category('IA'),
            $this->category('Architecture'),
        ]);

        $completion = $component->render()->crawler()->filter('[data-test="link-duplicate-completion"]');

        self::assertCount(0, $completion->filter('[data-test="link-duplicate-complete"]'));
        self::assertStringContainsString(
            'Il reste 1 place sur ce lien pour vos 2 catégories : décochez-en 1',
            $completion->text(),
        );
    }

    public function testDuplicateCompletedMeanwhileByAnotherMemberIsRenderedAgain(): void
    {
        $link = $this->existingLink(['Symfony', 'Agents']);
        $component = $this->component()->set('link.url', $link->getUrl())->set('link.categories', [$this->category(
            'IA',
        )]);
        $component->render();
        $linkManager = self::getContainer()->get(LinkManager::class);
        self::assertInstanceOf(LinkManager::class, $linkManager);
        $links = self::getContainer()->get(LinkRepository::class);
        self::assertInstanceOf(LinkRepository::class, $links);
        $linkManager->complete($links->find((int) $link->getId()) ?? self::fail(), [
            $this->categories()->find($this->category('Architecture')) ?? self::fail(),
        ]);

        $crawler = $component->call('completeDuplicate')->render()->crawler();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'complété par quelqu\'un d\'autre',
            $crawler->filter('[data-test="link-duplicate-notice"]')->text(),
        );
        self::assertCount(0, $crawler->filter('[data-test="link-duplicate-completion"]'));
    }

    public function testDuplicateWithThreeCategoriesOffersNothing(): void
    {
        $link = $this->existingLink(['Symfony', 'Agents', 'IA']);
        $component = $this->component()->set('link.url', $link->getUrl())->set('link.categories', [$this->category(
            'Architecture',
        )]);

        $crawler = $component->render()->crawler();

        self::assertCount(1, $crawler->filter('[data-test="link-duplicate"]'));
        self::assertCount(0, $crawler->filter('[data-test="link-duplicate-completion"]'));
    }

    /** @param list<string> $categoryNames */
    private function existingLink(array $categoryNames): Link
    {
        $url = 'https://example.com/' . uniqid('existant-');
        $categories = array_map(
            fn(string $name): Category => $this->categories()->find($this->category($name)) ?? self::fail(),
            $categoryNames,
        );
        $link = new Link($url, $url, $categories, $this->user(), new \DateTimeImmutable(), SourceType::Article);
        $repository = self::getContainer()->get(LinkRepository::class);
        self::assertInstanceOf(LinkRepository::class, $repository);
        $repository->save($link);

        return $link;
    }
}
