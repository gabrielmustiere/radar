<?php

declare(strict_types=1);

namespace App\Tests\Twig\Components;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Service\CategoryResolver;
use App\Twig\Components\LinkForm;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

/** Composant LinkForm piloté comme par le navigateur, connecté en admin@example.com sur les fixtures de test. */
abstract class LinkFormTestCase extends WebTestCase
{
    use InteractsWithLiveComponents;

    private KernelBrowser $client;

    #[\Override]
    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    protected function component(): TestLiveComponent
    {
        return $this->createLiveComponent('LinkForm', [], $this->client)->actingAs($this->user());
    }

    protected function linkForm(TestLiveComponent $component): LinkForm
    {
        $form = $component->component();
        self::assertInstanceOf(LinkForm::class, $form);

        return $form;
    }

    /** @return list<int> */
    protected function checkedIds(TestLiveComponent $component): array
    {
        /** @var array{categories?: list<int|string>} $values */
        $values = $this->linkForm($component)->formValues;

        return array_map(intval(...), $values['categories'] ?? []);
    }

    protected function pill(Crawler $crawler, string $name): Crawler
    {
        $pill = $crawler
            ->filter('[data-test="category-pill"]')
            ->reduce(static fn(Crawler $node): bool => $node->text() === $name);
        self::assertCount(1, $pill, sprintf('Pastille « %s » introuvable.', $name));

        return $pill;
    }

    protected function category(string $name): int
    {
        $resolver = self::getContainer()->get(CategoryResolver::class);
        self::assertInstanceOf(CategoryResolver::class, $resolver);

        return (int) $resolver->resolve($name)->getId();
    }

    protected function categories(): CategoryRepository
    {
        $repository = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $repository);

        return $repository;
    }

    protected function user(): User
    {
        $repository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);
        $user = $repository->findOneBy(['email' => 'admin@example.com']);
        self::assertNotNull($user);

        return $user;
    }
}
